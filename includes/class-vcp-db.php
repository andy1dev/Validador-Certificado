<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class VCP_DB {
    public static function create_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        // Tabla de cédulas permitidas
        $table_name = $wpdb->prefix . 'validador_cedulas';
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            cedula varchar(50) NOT NULL,
            creado_en datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY cedula (cedula)
        ) $charset_collate;";
        dbDelta( $sql );

        // Tabla de registro de descargas
        $table_descargas = $wpdb->prefix . 'validador_cedulas_descargas';
        $sql_descargas = "CREATE TABLE $table_descargas (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            cedula varchar(50) NOT NULL,
            nombres varchar(255) NOT NULL,
            fecha datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        dbDelta( $sql_descargas );
    }
}
