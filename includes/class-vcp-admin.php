<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class VCP_Admin {
    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'add_admin_menu' ) );
        add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
        add_action( 'admin_post_vcp_process_csv', array( __CLASS__, 'process_csv_upload' ) );
        add_action( 'admin_post_vcp_manage_cedula', array( __CLASS__, 'manage_cedula' ) );
        add_action( 'admin_post_vcp_delete_cedula', array( __CLASS__, 'delete_cedula' ) );
        add_action( 'admin_post_vcp_export_downloads', array( __CLASS__, 'export_downloads_csv' ) );
    }

    public static function add_admin_menu() {
        add_menu_page(
            'Validador de Cédulas',
            'Cédulas Pro',
            'manage_options',
            'vcp-admin',
            array( __CLASS__, 'admin_page_html' ),
            'dashicons-id',
            25
        );
    }

    public static function register_settings() {
        register_setting( 'vcp_options_group', 'vcp_pdf_url' );
        register_setting( 'vcp_options_group', 'vcp_pdf_x' );
        register_setting( 'vcp_options_group', 'vcp_pdf_y' );
        register_setting( 'vcp_options_group', 'vcp_pdf_font_size' );
        register_setting( 'vcp_options_group', 'vcp_pdf_font_type' );
    }

    public static function admin_page_html() {
        if ( ! current_user_can( 'manage_options' ) ) return;
        ?>
        <div class="wrap">
            <h1>Configuración de Validador de Cédulas Pro</h1>
            
            <h2 class="nav-tab-wrapper">
                <a href="#tab-1" class="nav-tab nav-tab-active">Subir CSV de Cédulas</a>
                <a href="#tab-2" class="nav-tab">Configuración del PDF</a>
                <a href="#tab-3" class="nav-tab">Gestión de Cédulas</a>
                <a href="#tab-4" class="nav-tab">Registro de Descargas</a>
            </h2>

            <div id="tab-1" class="tab-content" style="margin-top: 20px;">
                <h3>Subir Cédulas (CSV)</h3>
                <form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field( 'vcp_csv_upload_nonce', 'vcp_csv_nonce' ); ?>
                    <input type="hidden" name="action" value="vcp_process_csv">
                    <input type="file" name="vcp_csv_file" accept=".csv" required>
                    <?php submit_button( 'Subir y Procesar CSV' ); ?>
                </form>
            </div>

            <div id="tab-2" class="tab-content" style="display:none; margin-top: 20px;">
                <h3>Configuración del Certificado PDF</h3>
                <form method="post" action="options.php">
                    <?php 
                    settings_fields( 'vcp_options_group' ); 
                    do_settings_sections( 'vcp_options_group' ); 
                    ?>
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row">URL del PDF Base</th>
                            <td><input type="url" name="vcp_pdf_url" value="<?php echo esc_attr( get_option('vcp_pdf_url') ); ?>" class="regular-text" placeholder="https://.../certificado.pdf" required/>
                            <p class="description">Sube el PDF en Medios y pega aquí la URL completa.</p></td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">Posición X (mm)</th>
                            <td><input type="number" step="0.1" name="vcp_pdf_x" value="<?php echo esc_attr( get_option('vcp_pdf_x') ); ?>" required/></td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">Posición Y (mm)</th>
                            <td><input type="number" step="0.1" name="vcp_pdf_y" value="<?php echo esc_attr( get_option('vcp_pdf_y') ); ?>" required/></td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">Tamaño de Fuente</th>
                            <td><input type="number" name="vcp_pdf_font_size" value="<?php echo esc_attr( get_option('vcp_pdf_font_size') ); ?>" required/></td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">Tipo de Fuente</th>
                            <td>
                                <select name="vcp_pdf_font_type">
                                    <option value="Arial" <?php selected( get_option('vcp_pdf_font_type'), 'Arial' ); ?>>Arial</option>
                                    <option value="Courier" <?php selected( get_option('vcp_pdf_font_type'), 'Courier' ); ?>>Courier</option>
                                    <option value="Times" <?php selected( get_option('vcp_pdf_font_type'), 'Times' ); ?>>Times</option>
                                </select>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button( 'Guardar Configuración' ); ?>
                </form>
            </div>

            <div id="tab-3" class="tab-content" style="display:none; margin-top: 20px;">
                <h3>Gestión de Cédulas</h3>

                <!-- Formulario para Agregar / Editar -->
                <?php
                $edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
                $edit_cedula = '';
                if ($edit_id > 0) {
                    global $wpdb;
                    $table_name = $wpdb->prefix . 'validador_cedulas';
                    $edit_cedula = $wpdb->get_var($wpdb->prepare("SELECT cedula FROM $table_name WHERE id = %d", $edit_id));
                }
                ?>
                <div style="background: #fff; padding: 15px; border: 1px solid #ccd0d4; margin-bottom: 20px;">
                    <h4><?php echo $edit_id > 0 ? 'Editar Cédula' : 'Agregar Cédula Manualmente'; ?></h4>
                    <form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post">
                        <?php wp_nonce_field( 'vcp_manage_cedula_nonce', 'vcp_manage_nonce' ); ?>
                        <input type="hidden" name="action" value="vcp_manage_cedula">
                        <input type="hidden" name="id" value="<?php echo esc_attr($edit_id); ?>">
                        <input type="text" name="vcp_cedula" value="<?php echo esc_attr($edit_cedula); ?>" placeholder="Número de cédula" pattern="[0-9]*" required>
                        <?php submit_button( $edit_id > 0 ? 'Actualizar Cédula' : 'Agregar Cédula', 'primary', 'submit', false ); ?>
                        <?php if($edit_id > 0): ?>
                            <a href="?page=vcp-admin#tab-3" class="button">Cancelar</a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Buscador -->
                <form method="get" action="">
                    <input type="hidden" name="page" value="vcp-admin">
                    <p class="search-box">
                        <label class="screen-reader-text" for="cedula-search-input">Buscar Cédula:</label>
                        <input type="search" id="cedula-search-input" name="s" value="<?php echo isset($_GET['s']) ? esc_attr($_GET['s']) : ''; ?>">
                        <input type="submit" id="search-submit" class="button" value="Buscar Cédula">
                    </p>
                </form>

                <!-- Tabla de Resultados -->
                <?php
                global $wpdb;
                $table_name = $wpdb->prefix . 'validador_cedulas';
                
                // Paginación y Búsqueda
                $per_page = 20;
                $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
                $offset = ($paged - 1) * $per_page;
                $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
                
                $where = '';
                if ( ! empty($search) ) {
                    $where = $wpdb->prepare("WHERE cedula LIKE %s", '%' . $wpdb->esc_like($search) . '%');
                }

                $total_items = $wpdb->get_var("SELECT COUNT(id) FROM $table_name $where");
                $total_pages = ceil($total_items / $per_page);
                $items = $wpdb->get_results("SELECT * FROM $table_name $where ORDER BY id DESC LIMIT $offset, $per_page");
                ?>
                
                <table class="wp-list-table widefat fixed striped" style="margin-top:10px;">
                    <thead>
                        <tr>
                            <th scope="col" class="manage-column column-primary" style="width: 50px;">ID</th>
                            <th scope="col" class="manage-column">Cédula</th>
                            <th scope="col" class="manage-column">Fecha de Creación</th>
                            <th scope="col" class="manage-column" style="width: 150px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( ! empty($items) ): ?>
                            <?php foreach($items as $item): ?>
                                <tr>
                                    <td class="column-primary"><?php echo esc_html($item->id); ?></td>
                                    <td><?php echo esc_html($item->cedula); ?></td>
                                    <td><?php echo esc_html($item->creado_en); ?></td>
                                    <td>
                                        <a href="?page=vcp-admin&edit=<?php echo $item->id; ?>#tab-3">Editar</a> | 
                                        <a href="<?php echo wp_nonce_url( admin_url('admin-post.php?action=vcp_delete_cedula&id=' . $item->id), 'vcp_delete_cedula_nonce', 'vcp_delete_nonce' ); ?>" onclick="return confirm('¿Seguro que deseas eliminar esta cédula?');" style="color: red;">Eliminar</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4">No se encontraron cédulas.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <?php if($total_pages > 1): ?>
                    <div class="tablenav bottom">
                        <div class="tablenav-pages">
                            <span class="displaying-num"><?php echo $total_items; ?> elementos</span>
                            <span class="pagination-links">
                                <?php
                                $page_links = paginate_links( array(
                                    'base' => add_query_arg( 'paged', '%#%' ),
                                    'format' => '',
                                    'prev_text' => '&laquo;',
                                    'next_text' => '&raquo;',
                                    'total' => $total_pages,
                                    'current' => $paged
                                ));
                                echo $page_links;
                                ?>
                            </span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div id="tab-4" class="tab-content" style="display:none; margin-top: 20px;">
                <h3>Registro de Descargas</h3>
                <p>Aquí puedes ver todas las personas que han descargado su certificado.</p>
                <p>
                    <a href="<?php echo esc_url( admin_url('admin-post.php?action=vcp_export_downloads') ); ?>" class="button button-primary">Descargar Registro CSV</a>
                </p>

                <?php
                $table_descargas = $wpdb->prefix . 'validador_cedulas_descargas';
                
                // Paginación para descargas
                $paged_desc = isset($_GET['paged_desc']) ? max(1, intval($_GET['paged_desc'])) : 1;
                $offset_desc = ($paged_desc - 1) * $per_page;
                
                $total_descargas = $wpdb->get_var("SELECT COUNT(id) FROM $table_descargas");
                $total_pages_desc = ceil($total_descargas / $per_page);
                $descargas = $wpdb->get_results("SELECT * FROM $table_descargas ORDER BY id DESC LIMIT $offset_desc, $per_page");
                ?>
                <table class="wp-list-table widefat fixed striped" style="margin-top:10px;">
                    <thead>
                        <tr>
                            <th scope="col" class="manage-column column-primary" style="width: 50px;">ID</th>
                            <th scope="col" class="manage-column">Cédula</th>
                            <th scope="col" class="manage-column">Nombres</th>
                            <th scope="col" class="manage-column">Fecha de Descarga</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( ! empty($descargas) ): ?>
                            <?php foreach($descargas as $desc): ?>
                                <tr>
                                    <td class="column-primary"><?php echo esc_html($desc->id); ?></td>
                                    <td><?php echo esc_html($desc->cedula); ?></td>
                                    <td><?php echo esc_html($desc->nombres); ?></td>
                                    <td><?php echo esc_html($desc->fecha); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4">Aún no hay descargas registradas.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <?php if($total_pages_desc > 1): ?>
                    <div class="tablenav bottom">
                        <div class="tablenav-pages">
                            <span class="displaying-num"><?php echo $total_descargas; ?> descargas</span>
                            <span class="pagination-links">
                                <?php
                                $page_links_desc = paginate_links( array(
                                    'base' => add_query_arg( 'paged_desc', '%#%' ) . '#tab-4',
                                    'format' => '',
                                    'prev_text' => '&laquo;',
                                    'next_text' => '&raquo;',
                                    'total' => $total_pages_desc,
                                    'current' => $paged_desc
                                ));
                                echo $page_links_desc;
                                ?>
                            </span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <script>
                jQuery(document).ready(function($) {
                    function activateTab(hash) {
                        if(hash && $(hash).length) {
                            $('.nav-tab').removeClass('nav-tab-active');
                            $('a[href="' + hash + '"]').addClass('nav-tab-active');
                            $('.tab-content').hide();
                            $(hash).show();
                        }
                    }

                    $('.nav-tab').click(function(e){
                        e.preventDefault();
                        var hash = $(this).attr('href');
                        activateTab(hash);
                        window.history.replaceState(null, null, hash);
                    });
                    
                    var currentHash = window.location.hash;
                    <?php if(isset($_GET['s']) || isset($_GET['paged']) || isset($_GET['edit'])): ?>
                        if(!currentHash) currentHash = '#tab-3';
                    <?php endif; ?>
                    <?php if(isset($_GET['paged_desc'])): ?>
                        if(!currentHash) currentHash = '#tab-4';
                    <?php endif; ?>
                    
                    if(currentHash) {
                        activateTab(currentHash);
                    }
                });
            </script>
        </div>
        <?php
    }

    public static function process_csv_upload() {
        if ( ! current_user_can('manage_options') ) wp_die( 'No tienes permisos.' );
        if ( ! isset( $_POST['vcp_csv_nonce'] ) || ! wp_verify_nonce( $_POST['vcp_csv_nonce'], 'vcp_csv_upload_nonce' ) ) wp_die( 'Error de seguridad.' );

        if ( isset($_FILES['vcp_csv_file']) && $_FILES['vcp_csv_file']['error'] == 0 ) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'validador_cedulas';
            
            $file = fopen( $_FILES['vcp_csv_file']['tmp_name'], "r" );
            if ( $file !== FALSE ) {
                while ( ($data = fgetcsv($file, 1000, ",")) !== FALSE ) {
                    $cedula = sanitize_text_field( $data[0] );
                    // Insertar ignorando duplicados
                    $wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO $table_name (cedula) VALUES (%s)", $cedula ) );
                }
                fclose($file);
            }
            wp_redirect( admin_url( 'admin.php?page=vcp-admin#tab-3' ) );
            exit;
        }
    }

    public static function manage_cedula() {
        if ( ! current_user_can('manage_options') ) wp_die( 'No tienes permisos.' );
        if ( ! isset( $_POST['vcp_manage_nonce'] ) || ! wp_verify_nonce( $_POST['vcp_manage_nonce'], 'vcp_manage_cedula_nonce' ) ) wp_die( 'Error de seguridad.' );

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $cedula = isset($_POST['vcp_cedula']) ? sanitize_text_field($_POST['vcp_cedula']) : '';

        if(!empty($cedula)) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'validador_cedulas';

            if($id > 0) {
                $wpdb->update($table_name, array('cedula' => $cedula), array('id' => $id));
            } else {
                $wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO $table_name (cedula) VALUES (%s)", $cedula ) );
            }
        }
        
        wp_redirect( admin_url( 'admin.php?page=vcp-admin#tab-3' ) );
        exit;
    }

    public static function delete_cedula() {
        if ( ! current_user_can('manage_options') ) wp_die( 'No tienes permisos.' );
        if ( ! isset( $_GET['vcp_delete_nonce'] ) || ! wp_verify_nonce( $_GET['vcp_delete_nonce'], 'vcp_delete_cedula_nonce' ) ) wp_die( 'Error de seguridad.' );

        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if($id > 0) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'validador_cedulas';
            $wpdb->delete($table_name, array('id' => $id));
        }
        
        wp_redirect( admin_url( 'admin.php?page=vcp-admin#tab-3' ) );
        exit;
    }

    public static function export_downloads_csv() {
        if ( ! current_user_can('manage_options') ) wp_die( 'No tienes permisos.' );

        global $wpdb;
        $table_descargas = $wpdb->prefix . 'validador_cedulas_descargas';
        
        $descargas = $wpdb->get_results("SELECT cedula, nombres, fecha FROM $table_descargas ORDER BY id DESC", ARRAY_A);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="registro_descargas_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Agregar BOM para que Excel lea los caracteres UTF-8 correctamente
        fputs($output, $bom =( chr(0xEF) . chr(0xBB) . chr(0xBF) ));

        fputcsv($output, array('Cédula', 'Nombres', 'Fecha de Descarga'));

        if ( ! empty($descargas) ) {
            foreach( $descargas as $row ) {
                fputcsv($output, $row);
            }
        }

        fclose($output);
        exit;
    }
}
