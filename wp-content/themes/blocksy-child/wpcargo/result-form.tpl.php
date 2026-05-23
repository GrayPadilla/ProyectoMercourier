<?php
$is_logged       = is_user_logged_in();
$tracking_number = $shipment_number;
$shipment_id     = wpcargo_trackform_shipment_number( $shipment_number );
$is_public_code  = preg_match('/^MERC-\d+$/', $tracking_number);

// ========================================
// MAPA DE FASES (flujo ideal del envío)
// ========================================
$shipment_steps = [
    'pendiente'     => 'Pedido registrado',
    'recogido'      => 'Paquete recogido',
    'en-ruta'       => 'En ruta',
    'recepcionado'  => 'Recepcionado',
    'entregado'     => 'Entregado',
];

// ========================================
// ESTADOS ESPECIALES (excepciones)
// ========================================
$special_statuses = [
    'no-contesta',
    'no-recogido',
    'no-recibido',
    'reprogramado',
    'anulado',
];
?>

<!-- ESTILOS INTEGRADOS -->
<style>
/* ========================================
   CONTENEDOR PRINCIPAL - TRACKING PÚBLICO
   ======================================== */
.wpcargo-public-result {
    max-width: 900px;
    margin: 0 auto;
    padding: 30px;
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
}

.wpcargo-public-result h3 {
    font-size: 24px;
    color: #2c3e50;
    margin-bottom: 25px;
    font-weight: 600;
    text-align: center;
}

.wpcargo-public-result > p {
    font-size: 15px;
    color: #555;
    margin: 12px 0;
    padding: 12px 20px;
    background: #f8f9fa;
    border-radius: 8px;
    border-left: 4px solid #007bff;
}

.wpcargo-public-result > p strong {
    color: #2c3e50;
    margin-right: 8px;
}

/* ========================================
   ALERTA DE ESTADOS ESPECIALES
   ======================================== */
.merc-special-status-alert {
    background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
    border: 2px solid #ffc107;
    border-radius: 10px;
    padding: 20px 25px;
    margin: 25px 0;
    text-align: center;
    font-size: 16px;
    color: #856404;
    box-shadow: 0 4px 12px rgba(255, 193, 7, 0.15);
}

.merc-special-status-alert strong {
    color: #664d03;
    font-size: 18px;
}

/* ========================================
   BARRA DE PROGRESO
   ======================================== */
.merc-tracking-progress {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin: 40px 0;
    position: relative;
    padding: 0 20px;
}

.merc-step {
    text-align: center;
    flex: 1;
    position: relative;
    color: #95a5a6;
    font-size: 13px;
    display: flex;
    flex-direction: column;
    align-items: center;
    transition: all 0.3s ease;
}

/* Línea conectora entre pasos */
.merc-step:not(:last-child)::before {
    content: '';
    position: absolute;
    top: 20px;
    left: 50%;
    width: calc(100% - 40px);
    height: 4px;
    background: #e0e0e0;
    z-index: 0;
    border-radius: 2px;
    transition: all 0.4s ease;
}

.merc-step.completed:not(:last-child)::before {
    background: linear-gradient(90deg, #28a745 0%, #20c997 100%);
}

/* Círculo del paso */
.merc-step-circle {
    width: 44px;
    height: 44px;
    background: #e0e0e0;
    border-radius: 50%;
    margin-bottom: 12px;
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    border: 3px solid #ffffff;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

/* Paso completado */
.merc-step.completed .merc-step-circle {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    transform: scale(1.05);
}

.merc-step.completed .merc-step-circle::after {
    content: '✓';
    color: #ffffff;
    font-size: 22px;
    font-weight: bold;
}

.merc-step.completed {
    color: #28a745;
    font-weight: 600;
}

/* Paso actual (en progreso) */
.merc-step.current .merc-step-circle {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    box-shadow: 0 0 0 6px rgba(0, 123, 255, 0.15);
    animation: pulse-merc 2s infinite;
    transform: scale(1.1);
}

.merc-step.current .merc-step-circle::before {
    content: '';
    position: absolute;
    width: 12px;
    height: 12px;
    background: #ffffff;
    border-radius: 50%;
    animation: spin-merc 1.5s linear infinite;
}

.merc-step.current {
    color: #007bff;
    font-weight: 700;
}

/* Texto del paso */
.merc-step span {
    display: block;
    line-height: 1.4;
    max-width: 100px;
    font-weight: inherit;
}

/* Animaciones */
@keyframes pulse-merc {
    0%, 100% { 
        box-shadow: 0 0 0 6px rgba(0, 123, 255, 0.15);
    }
    50% { 
        box-shadow: 0 0 0 12px rgba(0, 123, 255, 0.08);
    }
}

@keyframes spin-merc {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* ========================================
   RESPONSIVE - MÓVILES
   ======================================== */
@media (max-width: 768px) {
    .wpcargo-public-result {
        padding: 20px 15px;
        margin: 10px;
    }

    .wpcargo-public-result h3 {
        font-size: 20px;
    }

    .merc-tracking-progress {
        flex-direction: column;
        align-items: stretch;
        padding: 0 10px;
    }

    .merc-step {
        flex-direction: row;
        align-items: center;
        text-align: left;
        margin-bottom: 30px;
    }

    .merc-step:not(:last-child)::before {
        top: 52px;
        left: 21px;
        width: 4px;
        height: calc(100% + 8px);
    }

    .merc-step-circle {
        margin-bottom: 0;
        margin-right: 18px;
        flex-shrink: 0;
    }

    .merc-step span {
        max-width: none;
        flex: 1;
        font-size: 14px;
    }

    .merc-special-status-alert {
        padding: 15px 18px;
        font-size: 14px;
    }
}

@media (max-width: 480px) {
    .wpcargo-public-result {
        border-radius: 8px;
    }

    .merc-step-circle {
        width: 38px;
        height: 38px;
    }

    .merc-step.completed .merc-step-circle::after {
        font-size: 18px;
    }
}
</style>

<?php if ( ! empty( $shipment_id ) ) : ?>
    <?php if ( $is_logged ) : ?>
        <!-- ========================= -->
        <!-- USUARIO LOGUEADO -->
        <!-- ========================= -->
        <?php
        $shipment               = new stdClass;
        $shipment->ID           = (int) esc_html( $shipment_id );
        $shipment->post_title   = esc_html( get_the_title( $shipment_id ) );
        $shipment_status        = esc_html( get_post_meta( $shipment->ID, 'wpcargo_status', true ) );
        $class_status           = strtolower( $shipment_status );
        $class_status           = str_replace( ' ', '_', $class_status );
        do_action( 'wpcargo_before_search_result' );
        do_action( 'wpcargo_print_btn' );
        ?>
        <div id="wpcargo-result-print"
             class="wpcargo-wrap-details wpcargo-container <?php echo esc_attr( $class_status ); ?>">
            <?php
            do_action( 'wpcargo_before_track_details', $shipment );
            do_action( 'wpcargo_track_header_details', $shipment );
            do_action( 'wpcargo_track_after_header_details', $shipment );
            do_action( 'wpcargo_track_shipper_details', $shipment );
            do_action( 'wpcargo_before_shipment_details', $shipment );
            do_action( 'wpcargo_track_shipment_details', $shipment );
            do_action( 'wpcargo_after_package_details', $shipment );
            if ( wpcargo_package_settings()->frontend_enable ) {
                do_action( 'wpcargo_after_package_totals', $shipment );
            }
            do_action( 'wpcargo_after_track_details', $shipment );
            ?>
        </div>

    <?php elseif ( $is_public_code ) : ?>
        <!-- ========================= -->
        <!-- USUARIO PÚBLICO (MERC-*) -->
        <!-- ========================= -->
        <?php
        $shipment               = new stdClass;
        $shipment->ID           = (int) esc_html( $shipment_id );
        $shipment->post_title   = esc_html( get_the_title( $shipment_id ) );
        $shipment_status        = esc_html( get_post_meta( $shipment->ID, 'wpcargo_status', true ) );

        // ========================================
        // NORMALIZAR EL ESTADO (CRÍTICO)
        // ========================================
        $current_status_slug = sanitize_title( $shipment_status );
        $step_keys = array_keys($shipment_steps);
        $current_step_index = array_search($current_status_slug, $step_keys);

        // Si no está en el flujo ideal, verificar si es especial
        $is_special_status = in_array($current_status_slug, $special_statuses);

        if ($current_step_index === false && !$is_special_status) {
            $current_step_index = 0; // fallback seguro
        }
        ?>

        <div class="wpcargo-public-result">
            <h3>Estado del envío</h3>
            <p>
                <strong>Código:</strong>
                <?php echo esc_html( $tracking_number ); ?>
            </p>
            <p>
                <strong>Estado actual:</strong>
                <?php echo esc_html( $shipment_status ); ?>
            </p>

            <?php
            // ========================================
            // ALERTA PARA ESTADOS ESPECIALES
            // ========================================
            if ( $is_special_status ) : ?>
                <div class="merc-special-status-alert">
                    ⚠️ Estado del envío: <strong><?php echo esc_html( $shipment_status ); ?></strong>
                </div>
            <?php else : ?>
                <!-- ========================================
                     BARRA DE PROGRESO (SOLO FLUJO NORMAL)
                     ======================================== -->
                <div class="merc-tracking-progress">
                    <?php foreach ( $shipment_steps as $step_slug => $step_label ): 
                        $step_index = array_search($step_slug, $step_keys);

                        $class = '';
                        if ( $step_index < $current_step_index ) {
                            $class = 'completed';
                        } elseif ( $step_index === $current_step_index ) {
                            $class = 'current';
                        }
                    ?>
                        <div class="merc-step <?php echo esc_attr($class); ?>">
                            <div class="merc-step-circle"></div>
                            <span><?php echo esc_html($step_label); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php
            /**
             * SOLO datos del paquete (peso, piezas, etc.)
             * NO historial
             */
            do_action( 'wpcargo_track_shipment_details', $shipment );
            ?>
        </div>
    <?php endif; ?>

<?php else : ?>
    <h3 style="color:red;text-align:center;padding:15px;">
        <?php echo apply_filters(
            'wpcargo_tn_no_result_text',
            esc_html__( 'No results found!', 'wpcargo' )
        ); ?>
    </h3>
<?php endif; ?>