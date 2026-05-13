<?php
/**
 * Plugin Name: Validador de Cédulas Pro
 * Plugin URI:  https://tusitio.com
 * Description: Valida cédulas desde una base de datos personalizada y genera certificados en PDF.
 * Version:     2.0
 * Author:      sptech
 * Text Domain: validador-cedulas-pro
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

define( 'VCP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'VCP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'VCP_PLUGIN_VERSION', '2.0' );

// Incluir archivos necesarios
require_once VCP_PLUGIN_DIR . 'includes/class-vcp-db.php';
require_once VCP_PLUGIN_DIR . 'includes/class-vcp-admin.php';
require_once VCP_PLUGIN_DIR . 'includes/class-vcp-shortcode.php';
require_once VCP_PLUGIN_DIR . 'includes/class-vcp-ajax.php';

// Activación del plugin
register_activation_hook( __FILE__, array( 'VCP_DB', 'create_table' ) );

// Inicializar clases
function vcp_init() {
    VCP_Admin::init();
    VCP_Shortcode::init();
    VCP_Ajax::init();
}
add_action( 'plugins_loaded', 'vcp_init' );

// Comprobar y actualizar la base de datos automáticamente
function vcp_check_db_update() {
    if ( get_option( 'vcp_db_version' ) !== '1.1.0' ) {
        VCP_DB::create_table();
        update_option( 'vcp_db_version', '1.1.0' );
    }
}
add_action( 'admin_init', 'vcp_check_db_update' );

// Encolar scripts en el frontend
function vcp_enqueue_scripts() {
    wp_enqueue_style( 'vcp-frontend-css', VCP_PLUGIN_URL . 'assets/css/vcp-frontend.css', array(), VCP_PLUGIN_VERSION );
    wp_enqueue_script( 'vcp-frontend-js', VCP_PLUGIN_URL . 'assets/js/vcp-frontend.js', array( 'jquery' ), VCP_PLUGIN_VERSION, true );

    // Pasar variables a JavaScript
    wp_localize_script( 'vcp-frontend-js', 'vcp_ajax', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'vcp_frontend_nonce' )
    ) );
}
add_action( 'wp_enqueue_scripts', 'vcp_enqueue_scripts' );
