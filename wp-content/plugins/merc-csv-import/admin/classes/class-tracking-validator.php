<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * MERC_Tracking_Validator
 * Corresponde a STEP 0A + STEP 0 + STEP 3 + STEP 3.5 del functions.php original.
 *
 * - Pre-inserción: garantiza unicidad del post_title (tracking number).
 * - Post-import paso 3: validación de duplicados por tracking.
 * - Post-import paso 3: validación por datos de remitente + destinatario.
 */
class MERC_Tracking_Validator {

	public function __construct() {
		// STEP 0A – antes de insertar en DB
		add_filter( 'wp_insert_post_data', [ $this, 'ensure_unique_tracking' ], 10, 2 );

		// STEP 0 – inmediatamente después de importar (prioridad 3)
		add_action( 'wpcie_after_save_csv_import', [ $this, 'validate_duplicate' ], 3, 2 );

		// STEP 3 – validación final (prioridad 15)
		add_action( 'wpcie_after_save_csv_import', [ $this, 'validate_duplicate_final' ], 15, 2 );

		// STEP 3.5 – validación por remitente + destinatario (prioridad 16)
		add_action( 'wpcie_after_save_csv_import', [ $this, 'validate_duplicate_by_data' ], 16, 2 );
	}

	/* ── STEP 0A: pre-inserción ──────────────────────────────────────── */

	public function ensure_unique_tracking( array $data, array $postarr ): array {
		global $wpdb;
		error_log('🔍 [TRACKING_VALIDATOR] Hook ejecutado para: ' . $data['post_title'] . ' (tipo: ' . $data['post_type'] . ')');
		
		if ( $data['post_type'] !== 'wpcargo_shipment' ) return $data;
		if ( ! empty( $postarr['ID'] ) ) return $data; // es actualización, no tocar

		$title = $data['post_title'];
		if ( empty( $title ) ) return $data;
		
		error_log('🔐 [LOCK_INIT] Iniciando validación de tracking para: ' . $title);

		// 🔐 LOCK A NIVEL DB: Row-level lock más fuerte que transientes
		// Usa wp_options como tabla de locks con transacciones
		$lock_key = 'merc_tracking_lock_' . md5( $title );
		$lock_acquired = false;
		$lock_value = microtime( true );

		// Intentar adquirir candado (máximo 2 segundos con reintentos)
		$lock_attempts = 0;
		while ( $lock_attempts < 20 && ! $lock_acquired ) {
			// Intento de insert directo (más atómico que set_transient)
			$wpdb->suppress_errors = true;
			$result = $wpdb->query( $wpdb->prepare(
				"INSERT IGNORE INTO {$wpdb->options} (option_name, option_value, autoload) 
				 VALUES (%s, %s, 'no')",
				$lock_key,
				$lock_value
			) );
			$wpdb->suppress_errors = false;

			if ( $result ) {
				$lock_acquired = true;
				error_log('🔓 [LOCK_ACQUIRED] Candado adquirido para: ' . $title);
				break;
			}
			$lock_attempts++;
			usleep( 100000 ); // 100ms backoff
		}

		// Si lock falló: verificar si tracking ya existe (ej: de la primera llamada del post/revision)
		if ( ! $lock_acquired ) {
			error_log('❌ [LOCK_FAILED] Candado no adquirido después de ' . $lock_attempts . ' intentos: ' . $title);
			
			// Si el tracking LIMPIO ya existe, es seguro permitir (es la misma shipment, post vs revisión)
			if ( $this->tracking_exists_clean( $title ) ) {
				error_log('✅ [LOCK_FAILED_BUT_EXISTS_CLEAN] Tracking limpio ' . $title . ' ya existe, permitiendo (revisión/autosave)');
				// ⚠️ IMPORTANTE: Restaurar el título limpio por si vino contaminado
				$data['post_title'] = $title;
				$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name=%s", $lock_key ) );
				return $data;
			}
			
			// Si existe pero contaminado (con suffix), también permitir pero restaurar limpio
			if ( $this->tracking_exists( $title ) ) {
				error_log('✅ [LOCK_FAILED_BUT_EXISTS_CONTAMINATED] Tracking contaminado detectado para ' . $title . ', usando limpio');
				$data['post_title'] = $title;
				$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name=%s", $lock_key ) );
				return $data;
			}
			
			// Si NO existe en ninguna forma, algo anomalo - aumentar número
			error_log('⚠️  [LOCK_FAILED_NEW_NUMBER] Lock falló y tracking no existe, buscando siguiente número para: ' . $title);
			if ( preg_match( '/^(MERC-)?(\d+)$/', $title, $m ) ) {
				$prefix = ! empty( $m[1] ) ? $m[1] : 'MERC-';
				$number = (int) $m[2];
				$length = strlen( $m[2] );
				$tries  = 0;
				
				do {
					$number++;
					$new = $prefix . str_pad( $number, $length, '0', STR_PAD_LEFT );
					$tries++;
				} while ( $this->tracking_exists_clean( $new ) && $tries < 100 );
				
				$data['post_title'] = $new;
				error_log('✅ [LOCK_FAILED_INCREMENTED] Usando número incrementado: ' . $new);
			}
			
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name=%s", $lock_key ) );
			return $data;
		}

		// Lock adquirido exitosamente, verificar si ya existe
		if ( ! $this->tracking_exists( $title ) ) {
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name=%s", $lock_key ) );
			return $data;
		}

		// Existe: incrementar número
		if ( preg_match( '/^(MERC-)?(\d+)$/', $title, $m ) ) {
			$prefix = ! empty( $m[1] ) ? $m[1] : 'MERC-';
			$number = (int) $m[2];
			$length = strlen( $m[2] );
			$tries  = 0;
			
			// Buscar el próximo número disponible (sin suffix, solo clean)
			do {
				$number++;
				$new = $prefix . str_pad( $number, $length, '0', STR_PAD_LEFT );
				$tries++;
			} while ( $this->tracking_exists_clean( $new ) && $tries < 100 );

			// Si tras 100 intentos aún hay colisión, algo está mal - pero NO aplicar suffix
			if ( $tries >= 100 ) {
				error_log('⚠️  [TRACKING_INCREMENT_EXHAUSTED] No se encontró número disponible después de 100 intentos para: ' . $title);
				// Simplemente permitir el siguiente número aunque exista (último recurso)
				$data['post_title'] = $new;
			} else {
				$data['post_title'] = $new;
			}
		} else {
			// Si no es formato MERC-XXXX, buscar incremento genérico o rechazar
			error_log('⚠️  [TRACKING_NON_STANDARD] Formato no estándar: ' . $title);
			$data['post_title'] = $title; // Mantener como está
		}

		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name=%s", $lock_key ) );
		return $data;
	}

	/* ── STEP 0: duplicado reciente (60s) ───────────────────────────── */

	public function validate_duplicate( int $shipment_id, array $record ): void {
		global $wpdb;
		[ $check, $post_title ] = $this->get_tracking_and_title( $shipment_id );
		if ( ! $check ) return;

		// ✅ STEP 0A ya validó trackings LIMPIOS que son NUEVOS
		// Pero si un tracking LIMPIO ya existe en BD, es un duplicado real
		if ( preg_match( '/^MERC-\d+$/', $check ) ) {
			// Verificar si este tracking limpio ya existe en BD (no contar el actual)
			$dups_limpio = (int) $wpdb->get_var( $wpdb->prepare( // phpcs:ignore
				"SELECT COUNT(*) FROM {$wpdb->posts}
				 WHERE post_type='wpcargo_shipment' AND post_status='publish'
				 AND post_title=%s AND ID!=%d",
				$check, $shipment_id
			) );
			
			if ( $dups_limpio === 0 ) {
				// No existe tracking limpio en BD = fue validado por STEP 0A, skip
				error_log('⏭️  [VALIDATE_DUPLICATE] Tracking LIMPIO es nuevo (validado por STEP 0A): ' . $check);
				return;
			}
			
			// Existe tracking limpio en BD = true duplicado, INCREMENTAR SIN SUFFIX
			error_log('🔍 [VALIDATE_DUPLICATE] Tracking LIMPIO ya existe en BD: ' . $check . ', buscando siguiente número limpio');
			
			if ( preg_match( '/^(MERC-)?(\d+)$/', $check, $m ) ) {
				$prefix = ! empty( $m[1] ) ? $m[1] : 'MERC-';
				$number = (int) $m[2];
				$length = strlen( $m[2] );
				$tries  = 0;
				
				// Buscar el próximo número disponible (sin suffix, solo clean)
				do {
					$number++;
					$new = $prefix . str_pad( $number, $length, '0', STR_PAD_LEFT );
					$tries++;
				} while ( $this->tracking_exists_clean( $new ) && $tries < 100 );
				
				error_log('✅ [VALIDATE_DUPLICATE] Usando número incrementado sin suffix: ' . $new);
				wp_update_post( [ 'ID' => $shipment_id, 'post_title' => $new ] );
				update_post_meta( $shipment_id, 'wpcargo_original_tracking', $check );
				update_post_meta( $shipment_id, 'wpcargo_duplicate_attempt', 'yes' );
				return;
			}
		}

		$dups = (int) $wpdb->get_var( $wpdb->prepare( // phpcs:ignore
			"SELECT COUNT(*) FROM {$wpdb->posts}
			 WHERE post_type='wpcargo_shipment' AND post_status='publish'
			 AND post_title=%s AND ID!=%d
			 AND post_date > DATE_SUB(NOW(), INTERVAL 60 SECOND)",
			$check, $shipment_id
		) );

		if ( $dups > 0 ) {
			error_log('⚠️  [VALIDATE_DUPLICATE] Duplicado reciente detectado: ' . $check . ', buscando siguiente número');
			// Buscar siguiente número sin suffix
			if ( preg_match( '/^(MERC-)?(\d+)$/', $check, $m ) ) {
				$prefix = ! empty( $m[1] ) ? $m[1] : 'MERC-';
				$number = (int) $m[2];
				$length = strlen( $m[2] );
				$tries  = 0;
				
				do {
					$number++;
					$new = $prefix . str_pad( $number, $length, '0', STR_PAD_LEFT );
					$tries++;
				} while ( $this->tracking_exists_clean( $new ) && $tries < 100 );
				
				error_log('✅ [VALIDATE_DUPLICATE] Usando número incrementado: ' . $new);
				wp_update_post( [ 'ID' => $shipment_id, 'post_title' => $new ] );
				update_post_meta( $shipment_id, 'wpcargo_original_tracking', $check );
				update_post_meta( $shipment_id, 'wpcargo_duplicate_attempt', 'yes' );
			}
		}
	}

	/* ── STEP 3: duplicado global ────────────────────────────────────── */

	public function validate_duplicate_final( int $shipment_id, array $record ): void {
		global $wpdb;
		[ $check, $post_title ] = $this->get_tracking_and_title( $shipment_id );
		if ( ! $check ) return;

		// ✅ STEP 0A ya validó trackings LIMPIOS que son NUEVOS
		// Pero si un tracking LIMPIO ya existe en BD, es un duplicado real
		if ( preg_match( '/^MERC-\d+$/', $check ) ) {
			// Verificar si este tracking limpio ya existe en BD (no contar el actual)
			$dups_limpio = (int) $wpdb->get_var( $wpdb->prepare( // phpcs:ignore
				"SELECT COUNT(*) FROM {$wpdb->posts}
				 WHERE post_type='wpcargo_shipment' AND post_status='publish'
				 AND post_title=%s AND ID!=%d",
				$check, $shipment_id
			) );
			
			if ( $dups_limpio === 0 ) {
				// No existe tracking limpio en BD = fue validado por STEP 0A, skip
				error_log('⏭️  [VALIDATE_DUPLICATE_FINAL] Tracking LIMPIO es nuevo (validado por STEP 0A): ' . $check);
				return;
			}
			
			// Existe tracking limpio en BD = true duplicado, INCREMENTAR SIN SUFFIX
			error_log('🔍 [VALIDATE_DUPLICATE_FINAL] Tracking LIMPIO ya existe en BD: ' . $check . ', buscando siguiente número limpio');
			
			if ( preg_match( '/^(MERC-)?(\d+)$/', $check, $m ) ) {
				$prefix = ! empty( $m[1] ) ? $m[1] : 'MERC-';
				$number = (int) $m[2];
				$length = strlen( $m[2] );
				$tries  = 0;
				
				// Buscar el próximo número disponible (sin suffix, solo clean)
				do {
					$number++;
					$new = $prefix . str_pad( $number, $length, '0', STR_PAD_LEFT );
					$tries++;
				} while ( $this->tracking_exists_clean( $new ) && $tries < 100 );
				
				error_log('✅ [VALIDATE_DUPLICATE_FINAL] Usando número incrementado sin suffix: ' . $new);
				wp_update_post( [ 'ID' => $shipment_id, 'post_title' => $new ] );
				update_post_meta( $shipment_id, 'wpcargo_original_tracking', $check );
				update_post_meta( $shipment_id, 'wpcargo_duplicate_attempt', 'yes' );
				return;
			}
		}

		$dups = (int) $wpdb->get_var( $wpdb->prepare( // phpcs:ignore
			"SELECT COUNT(*) FROM {$wpdb->posts}
			 WHERE post_type='wpcargo_shipment' AND post_status='publish'
			 AND post_title=%s AND ID!=%d",
			$check, $shipment_id
		) );

		if ( $dups > 0 ) {
			error_log('⚠️  [VALIDATE_DUPLICATE_FINAL] Duplicado global detectado: ' . $check . ', buscando siguiente número');
			// Buscar siguiente número sin suffix
			if ( preg_match( '/^(MERC-)?(\d+)$/', $check, $m ) ) {
				$prefix = ! empty( $m[1] ) ? $m[1] : 'MERC-';
				$number = (int) $m[2];
				$length = strlen( $m[2] );
				$tries  = 0;
				
				do {
					$number++;
					$new = $prefix . str_pad( $number, $length, '0', STR_PAD_LEFT );
					$tries++;
				} while ( $this->tracking_exists_clean( $new ) && $tries < 100 );
				
				error_log('✅ [VALIDATE_DUPLICATE_FINAL] Usando número incrementado: ' . $new);
				wp_update_post( [ 'ID' => $shipment_id, 'post_title' => $new ] );
				update_post_meta( $shipment_id, 'wpcargo_original_tracking', $check );
				update_post_meta( $shipment_id, 'wpcargo_duplicate_attempt', 'yes' );
			}
		}
	}

	/* ── STEP 3.5: duplicado por remitente + destinatario ────────────── */

	public function validate_duplicate_by_data( int $shipment_id, array $record ): void {
		global $wpdb;

		$sn = get_post_meta( $shipment_id, 'wpcargo_shipper_name',   true );
		$sp = get_post_meta( $shipment_id, 'wpcargo_shipper_phone',  true );
		$rn = get_post_meta( $shipment_id, 'wpcargo_receiver_name',  true );
		$rp = get_post_meta( $shipment_id, 'wpcargo_receiver_phone', true );

		if ( empty( $sn ) || empty( $rn ) || empty( $rp ) ) return;

		$norm  = fn( string $s ) => strtolower( trim( preg_replace( '/\s+/', ' ', $s ) ) );
		$strip = fn( string $s ) => preg_replace( '/[^0-9]/', '', $s );

		$candidates = $wpdb->get_col( $wpdb->prepare( // phpcs:ignore
			"SELECT DISTINCT p.ID FROM {$wpdb->posts} p
			 INNER JOIN {$wpdb->postmeta} a ON p.ID=a.post_id AND a.meta_key='wpcargo_shipper_name'
			 INNER JOIN {$wpdb->postmeta} b ON p.ID=b.post_id AND b.meta_key='wpcargo_shipper_phone'
			 INNER JOIN {$wpdb->postmeta} c ON p.ID=c.post_id AND c.meta_key='wpcargo_receiver_name'
			 INNER JOIN {$wpdb->postmeta} d ON p.ID=d.post_id AND d.meta_key='wpcargo_receiver_phone'
			 WHERE p.post_type='wpcargo_shipment' AND p.post_status='publish'
			 AND p.ID!=%d AND p.post_date > DATE_SUB(NOW(), INTERVAL 30 DAY)",
			$shipment_id
		) );

		foreach ( $candidates as $eid ) {
			$match_s = $norm( $sn ) === $norm( get_post_meta( $eid, 'wpcargo_shipper_name',  true ) )
			        && $strip( $sp ) === $strip( get_post_meta( $eid, 'wpcargo_shipper_phone', true ) );
			$match_r = $norm( $rn ) === $norm( get_post_meta( $eid, 'wpcargo_receiver_name',  true ) )
			        && $strip( $rp ) === $strip( get_post_meta( $eid, 'wpcargo_receiver_phone', true ) );

			if ( $match_s && $match_r ) {
				update_post_meta( $shipment_id, 'wpcargo_duplicate_sender_receiver', 'yes' );
				update_post_meta( $shipment_id, 'wpcargo_duplicate_of_shipment',     $eid );
				update_post_meta( $shipment_id, 'wpcargo_import_validation_error',
					"Duplicado: mismo remitente y destinatario que envío #{$eid}" );
				break;
			}
		}
	}

	/* ── Helpers ─────────────────────────────────────────────────────── */

	private function tracking_exists( string $title ): bool {
		global $wpdb;
		
		// Búsqueda exacta PRIMERO (para detectar el tracking limpio)
		$exact = (bool) $wpdb->get_var( $wpdb->prepare( // phpcs:ignore
			"SELECT ID FROM {$wpdb->posts}
			 WHERE post_type='wpcargo_shipment' AND post_status='publish'
			 AND post_title=%s LIMIT 1",
			$title
		) );
		
		if ( $exact ) {
			return true;
		}
		
		// Si no encontró exacto, buscar con PATRÓN (detecta trackings contaminados como MERC-002530-timestamp)
		// Esto evita crear duplicados cuando alguien ya creo el tracking con suffix
		$pattern = $title . '-%';
		$contaminated = (bool) $wpdb->get_var( $wpdb->prepare( // phpcs:ignore
			"SELECT ID FROM {$wpdb->posts}
			 WHERE post_type='wpcargo_shipment' AND post_status='publish'
			 AND post_title LIKE %s LIMIT 1",
			$pattern
		) );
		
		return $contaminated;
	}

	/**
	 * Solo busca tracking LIMPIOS (sin suffix)
	 * Usado para el incremento de números para evitar chocar con trackings contaminados
	 */
	private function tracking_exists_clean( string $title ): bool {
		global $wpdb;
		
		// Solo búsqueda exacta - NO incluir pattern de suffix
		return (bool) $wpdb->get_var( $wpdb->prepare( // phpcs:ignore
			"SELECT ID FROM {$wpdb->posts}
			 WHERE post_type='wpcargo_shipment' AND post_status='publish'
			 AND post_title=%s LIMIT 1",
			$title
		) );
	}

	private function get_tracking_and_title( int $id ): array {
		$tracking = get_post_meta( $id, 'wpcargo_tracking_number', true );
		$post     = get_post( $id );
		$title    = $post ? $post->post_title : '';
		$check    = $tracking ?: $title;
		if ( empty( $check ) || $check === 'SIN TÍTULO' ) return [ null, $title ];
		return [ $check, $title ];
	}
}

new MERC_Tracking_Validator();


