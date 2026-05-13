jQuery(document).ready(function($) {
    // Forzar que solo se puedan escribir números en el input de cédula en tiempo real
    $('#vcp-cedula').on('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
    });

    // Forzar mayúsculas en el input de nombres en tiempo real
    $('#vcp-nombres').on('input', function() {
        this.value = this.value.toUpperCase();
    });

    $('#vcp-btn-validar').on('click', function(e) {
        e.preventDefault();

        var cedula = $('#vcp-cedula').val();
        var $msg = $('#vcp-message');
        var $loader = $('#vcp-loader');

        // Validar que no esté vacío y solo sean números
        if (cedula.trim() === '' || !/^\d+$/.test(cedula)) {
            $msg.css({ 'color': '#d32f2f', 'background-color': '#fde0e0' })
                .text('Por favor, ingresa un número de cédula válido.')
                .show();
            return;
        }

        $loader.show();
        $msg.hide().text('');

        $.ajax({
            url: vcp_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'vcp_validate_cedula',
                security: vcp_ajax.nonce,
                cedula: cedula
            },
            success: function(response) {
                $loader.hide();
                if (response.success) {
                    $msg.css({ 'color': '#2e7d32', 'background-color': '#e8f5e9' })
                        .text(response.data.message)
                        .show();
                    
                    $('#vcp-step-2').css('display', 'block'); // La animación de CSS se encarga del resto
                    
                    // Bloquear el input de cédula
                    $('#vcp-cedula').prop('readonly', true).css('opacity', '0.7');
                    $('#vcp-btn-validar').prop('disabled', true);
                } else {
                    $msg.css({ 'color': '#d32f2f', 'background-color': '#fde0e0' })
                        .text(response.data.message)
                        .show();
                    $('#vcp-step-2').hide();
                }
            },
            error: function() {
                $loader.hide();
                $msg.css({ 'color': '#d32f2f', 'background-color': '#fde0e0' })
                    .text('Error de conexión con el servidor.')
                    .show();
            }
        });
    });

    // Antes de enviar el formulario de descarga, copiar los valores y asegurar mayúsculas
    $('#vcp-form-descarga').on('submit', function() {
        $('#vcp-hidden-cedula').val($('#vcp-cedula').val());
        $('#vcp-hidden-nombres').val($('#vcp-nombres').val().toUpperCase());
    });
});

