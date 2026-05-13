<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class VCP_Ajax {
    public static function init() {
        add_action( 'wp_ajax_vcp_validate_cedula', array( __CLASS__, 'validate_cedula' ) );
        add_action( 'wp_ajax_nopriv_vcp_validate_cedula', array( __CLASS__, 'validate_cedula' ) );

        add_action( 'wp_ajax_vcp_generate_pdf', array( __CLASS__, 'generate_pdf' ) );
        add_action( 'wp_ajax_nopriv_vcp_generate_pdf', array( __CLASS__, 'generate_pdf' ) );
    }

    public static function validate_cedula() {
        check_ajax_referer( 'vcp_frontend_nonce', 'security' );

        $cedula = isset( $_POST['cedula'] ) ? sanitize_text_field( $_POST['cedula'] ) : '';
        if ( empty( $cedula ) ) {
            wp_send_json_error( array( 'message' => 'Cédula no válida.' ) );
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'validador_cedulas';
        $exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table_name WHERE cedula = %s", $cedula ) );

        if ( $exists ) {
            wp_send_json_success( array( 'message' => 'Documento validado' ) );
        } else {
            wp_send_json_error( array( 'message' => 'Documento no encontrado, contáctate con el soporte para más información' ) );
        }
    }

    public static function generate_pdf() {
        check_admin_referer( 'vcp_frontend_nonce', 'security' );

        $cedula = isset( $_POST['cedula'] ) ? sanitize_text_field( $_POST['cedula'] ) : '';
        $nombres = isset( $_POST['nombres'] ) ? sanitize_text_field( $_POST['nombres'] ) : '';

        if ( empty($cedula) || empty($nombres) ) {
            wp_die( 'Datos faltantes.' );
        }

        // Forzar nombres a mayúsculas
        $nombres = mb_strtoupper($nombres, 'UTF-8');

        // Verificamos de nuevo por seguridad
        global $wpdb;
        $table_name = $wpdb->prefix . 'validador_cedulas';
        $exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table_name WHERE cedula = %s", $cedula ) );

        if ( ! $exists ) {
            wp_die( 'Cédula no autorizada.' );
        }

        // Registrar la descarga
        $table_descargas = $wpdb->prefix . 'validador_cedulas_descargas';
        $wpdb->insert(
            $table_descargas,
            array(
                'cedula' => $cedula,
                'nombres' => $nombres,
                'fecha' => current_time('mysql')
            )
        );

        // Requiere FPDF y FPDI (debes colocarlos en la carpeta lib)
        $fpdf_path = VCP_PLUGIN_DIR . 'lib/fpdf/fpdf.php';
        $fpdi_path = VCP_PLUGIN_DIR . 'lib/fpdi/autoload.php';

        if ( ! file_exists( $fpdf_path ) || ! file_exists( $fpdi_path ) ) {
            wp_die( 'Error: Las librerías FPDF y FPDI no están instaladas en la carpeta lib/ del plugin. Descárgalas e instálalas allí.' );
        }

        require_once $fpdf_path;
        require_once $fpdi_path;

        $pdf_url = get_option( 'vcp_pdf_url' );
        if ( empty( $pdf_url ) ) {
            wp_die( 'Error: No se ha configurado el PDF base en el panel de administración.' );
        }

        // Descargar PDF base temporalmente
        // Intenta usar la ruta local si el PDF está alojado en el mismo sitio
        $upload_dir = wp_upload_dir();
        $base_url = $upload_dir['baseurl'];
        $base_dir = $upload_dir['basedir'];
        
        $is_local = false;
        if ( strpos( $pdf_url, $base_url ) === 0 ) {
            $local_path = str_replace( $base_url, $base_dir, $pdf_url );
            if ( file_exists( $local_path ) ) {
                $temp_pdf = $local_path;
                $is_local = true;
            } else {
                $temp_pdf = download_url( $pdf_url );
            }
        } else {
            $temp_pdf = download_url( $pdf_url );
        }

        if ( is_wp_error( $temp_pdf ) ) {
            wp_die( 'Error al obtener el PDF base. Detalles: ' . $temp_pdf->get_error_message() );
        }

        // Configuración
        $x = (float) get_option( 'vcp_pdf_x', 50 );
        $y = (float) get_option( 'vcp_pdf_y', 50 );
        $font_size = (int) get_option( 'vcp_pdf_font_size', 16 );
        $font_type = get_option( 'vcp_pdf_font_type', 'Arial' );

        // Usando setasign\Fpdi\Fpdi (asumiendo FPDI v2)
        $pdf = new \setasign\Fpdi\Fpdi();
        $pdf->AddPage();
        $pdf->setSourceFile( $temp_pdf );
        $tplIdx = $pdf->importPage( 1 );
        $pdf->useTemplate( $tplIdx, 0, 0, null, null, true );

        $pdf->SetFont( $font_type, '', $font_size );
        $pdf->SetXY( $x, $y );
        // Codificar a ISO-8859-1 para compatibilidad con FPDF con acentos
        $texto_imprimir = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $nombres);
        $pdf->Cell( 0, 10, $texto_imprimir, 0, 1, 'L' );

        // Limpiar archivo temporal si fue descargado
        if ( ! $is_local ) {
            @unlink( $temp_pdf );
        }

        // Forzar descarga
        $pdf->Output( 'D', 'Certificado_' . $cedula . '.pdf' );
        exit;
    }
}
