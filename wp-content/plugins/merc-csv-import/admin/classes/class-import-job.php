<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class MERC_Import_Job {

    public static function table_name() {
        global $wpdb;
        return $wpdb->prefix . 'merc_import_jobs';
    }

    public static function install_table() {
        global $wpdb;
        $table = self::table_name();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            file_path VARCHAR(255) NOT NULL,
            original_name VARCHAR(255) NOT NULL,
            uploader BIGINT UNSIGNED NOT NULL DEFAULT 0,
            status VARCHAR(32) NOT NULL DEFAULT 'queued',
            rows_estimated INT NOT NULL DEFAULT 0,
            rows_processed INT NOT NULL DEFAULT 0,
            rows_failed INT NOT NULL DEFAULT 0,
            attempts INT NOT NULL DEFAULT 0,
            last_error LONGTEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    public static function create_job( string $file_path, string $original_name, int $uploader = 0, int $rows_estimated = 0 ) {
        global $wpdb;
        $table = self::table_name();
        $now = current_time( 'mysql' );
        $inserted = $wpdb->insert( $table, [
            'file_path'     => $file_path,
            'original_name' => $original_name,
            'uploader'      => $uploader,
            'status'        => 'queued',
            'rows_estimated'=> $rows_estimated,
            'rows_processed'=> 0,
            'rows_failed'   => 0,
            'attempts'      => 0,
            'created_at'    => $now,
            'updated_at'    => $now,
        ], [ '%s', '%s', '%d', '%s', '%d', '%d', '%s', '%s' ] );

        if ( $inserted ) {
            return (int) $wpdb->insert_id;
        }
        return 0;
    }

    public static function claim_next_job() {
        global $wpdb;
        $table = self::table_name();
        // simple claim: pick oldest queued job and set to processing
        $job = $wpdb->get_row( "SELECT * FROM {$table} WHERE status='queued' ORDER BY created_at ASC LIMIT 1" );
        if ( ! $job ) return null;
        $updated = $wpdb->update( $table, [ 'status' => 'processing', 'updated_at' => current_time( 'mysql' ) ], [ 'id' => $job->id ], [ '%s', '%s' ], [ '%d' ] );
        if ( $updated ) {
            $job->status = 'processing';
            return $job;
        }
        return null;
    }

    public static function update_status( int $id, string $status, ?string $last_error = null ) {
        global $wpdb;
        $table = self::table_name();
        $data = [ 'status' => $status, 'updated_at' => current_time( 'mysql' ) ];
        $format = [ '%s', '%s' ];
        if ( $last_error !== null ) {
            $data['last_error'] = $last_error;
            $format[] = '%s';
        }
        $wpdb->update( $table, $data, [ 'id' => $id ], $format, [ '%d' ] );
    }

    public static function update_metrics( int $id, int $processed_inc = 0, int $failed_inc = 0 ) {
        global $wpdb;
        $table = self::table_name();
        $wpdb->query( $wpdb->prepare( "UPDATE {$table} SET rows_processed = rows_processed + %d, rows_failed = rows_failed + %d, updated_at = %s WHERE id = %d", $processed_inc, $failed_inc, current_time( 'mysql' ), $id ) );
    }

}

