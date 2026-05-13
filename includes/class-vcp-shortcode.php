<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class VCP_Shortcode {
    public static function init() {
        add_shortcode( 'validador_cedulas', array( __CLASS__, 'render' ) );
    }

    public static function render() {
        ob_start();
        ?>
        <div id="vcp-container" class="vcp-wrapper">
            <h3>Validador de Certificados</h3>
            <div id="vcp-step-1">
                <label for="vcp-cedula">Ingrese su Cédula:</label>
                <input type="text" id="vcp-cedula" name="vcp-cedula" pattern="[0-9]*" inputmode="numeric" placeholder="Solo números" required>
                <button id="vcp-btn-validar" type="button">Validar</button>
            </div>

            <div id="vcp-loader" style="display: none;">Cargando...</div>
            <div id="vcp-message" style="margin-top: 10px;"></div>

            <div id="vcp-step-2" style="display: none; margin-top: 15px;">
                <label for="vcp-nombres">Ingrese sus nombres completos:</label>
                <input type="text" id="vcp-nombres" name="vcp-nombres" required>
                <!-- Formulario invisible para forzar la descarga del PDF via POST -->
                <form id="vcp-form-descarga" method="POST" action="<?php echo esc_url( admin_url('admin-ajax.php') ); ?>">
                    <input type="hidden" name="action" value="vcp_generate_pdf">
                    <input type="hidden" name="security" value="<?php echo wp_create_nonce('vcp_frontend_nonce'); ?>">
                    <input type="hidden" id="vcp-hidden-cedula" name="cedula" value="">
                    <input type="hidden" id="vcp-hidden-nombres" name="nombres" value="">
                    <button id="vcp-btn-generar" type="submit">Generar Certificado</button>
                </form>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
