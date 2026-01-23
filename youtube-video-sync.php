<?php
/**
 * Plugin Name: YouTube Video Sync
 * Description: Sincroniza videos de tu canal de YouTube con el CPT "videos" usando SCF. Ejecuta automáticamente a primera hora y permite sincronización manual.
 * Version: 1.0
 * Author: Eduardo Valenzuela
 */

if (!defined('ABSPATH')) exit;

class YouTube_Video_Sync {
    
    private $post_type = 'video';
    
    private function get_api_key() {
        return get_option('ytvs_api_key', '');
    }
    
    private function get_channel_id() {
        return get_option('ytvs_channel_id', '');
    }
    
    private $category_map = array(
        '1'  => 'baena', '10' => 'cabra', '20' => 'cordoba'
    );

    public function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        add_action('init', array($this, 'register_post_type_and_taxonomy'));
        add_action('init', array($this, 'register_custom_fields')); 
        add_action('youtube_sync_daily_event', array($this, 'sync_videos'));
        add_action('admin_menu', array($this, 'add_admin_pages'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_post_sync_youtube_manual', array($this, 'manual_sync'));
        add_filter('parent_file', array($this, 'set_parent_menu'));
        
        // Soporte para Elementor Dynamic Tags
        add_action('elementor/dynamic_tags/register', array($this, 'register_elementor_tags'));
    }

    public function activate() {
        if (!wp_next_scheduled('youtube_sync_daily_event')) {
            wp_schedule_event(strtotime('tomorrow 01:00'), 'daily', 'youtube_sync_daily_event');
        }
        
        // Precargar API Key y Channel ID si no existen
        if (!get_option('ytvs_api_key')) {
            add_option('ytvs_api_key', 'AIzaSyDjgY_vSpkYJwvbMNR1rzDk9evo7o48Pio');
        }
        if (!get_option('ytvs_channel_id')) {
            add_option('ytvs_channel_id', 'UCkP9R2e_9MyEOXLqwQstClg');
        }
        if (!get_option('ytvs_filter_keywords')) {
            add_option('ytvs_filter_keywords', 'formato 916, 9:16, #shorts, #short, vertical');
        }
        
        // Registrar CPT y taxonomía antes de hacer flush
        $this->register_post_type_and_taxonomy();
        flush_rewrite_rules();
    }

    public function deactivate() {
        wp_clear_scheduled_hook('youtube_sync_daily_event');
        delete_option('yt_sync_last_report');
        flush_rewrite_rules();
    }

    public function register_post_type_and_taxonomy() {
        // Registrar Custom Post Type "video"
        register_post_type('video', array(
            'label' => 'Videos',
            'labels' => array(
                'name' => 'Videos',
                'singular_name' => 'Video',
                'add_new' => 'Agregar Nuevo',
                'add_new_item' => 'Agregar Nuevo Video',
                'edit_item' => 'Editar Video',
                'new_item' => 'Nuevo Video',
                'view_item' => 'Ver Video',
                'search_items' => 'Buscar Videos',
                'not_found' => 'No se encontraron videos',
                'not_found_in_trash' => 'No se encontraron videos en la papelera'
            ),
            'public' => true,
            'has_archive' => true,
            'show_in_rest' => true,
            'show_in_menu' => false, // Ocultar menú principal, se accede desde YouTube Sync
            'supports' => array('title', 'editor', 'thumbnail', 'custom-fields'),
            'rewrite' => array('slug' => 'videos'),
            'taxonomies' => array('category', 'ciudad')
        ));

        // Registrar Taxonomía Custom "ciudad"
        register_taxonomy('ciudad', array('video'), array(
            'label' => 'Ciudades',
            'labels' => array(
                'name' => 'Ciudades',
                'singular_name' => 'Ciudad',
                'search_items' => 'Buscar Ciudades',
                'all_items' => 'Todas las Ciudades',
                'edit_item' => 'Editar Ciudad',
                'update_item' => 'Actualizar Ciudad',
                'add_new_item' => 'Agregar Nueva Ciudad',
                'new_item_name' => 'Nombre de Nueva Ciudad',
                'menu_name' => 'Ciudades'
            ),
            'hierarchical' => true,
            'show_ui' => true,
            'show_in_rest' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'ciudad')
        ));
    }

    public function register_custom_fields() {
        // Registrar campos personalizados para Elementor
        register_post_meta('video', 'youtube_url', array(
            'type' => 'string',
            'description' => 'URL del video de YouTube',
            'single' => true,
            'show_in_rest' => true
        ));

        register_post_meta('video', 'descripcion', array(
            'type' => 'string',
            'description' => 'Descripción del video',
            'single' => true,
            'show_in_rest' => true
        ));

        register_post_meta('video', '_youtube_video_id', array(
            'type' => 'string',
            'description' => 'ID del video de YouTube',
            'single' => true,
            'show_in_rest' => true
        ));

        register_post_meta('video', 'portada', array(
            'type' => 'integer',
            'description' => 'ID de la imagen de portada',
            'single' => true,
            'show_in_rest' => true
        ));
    }

    public function register_elementor_tags($dynamic_tags) {
        // Verificar si Elementor está activo
        if (!class_exists('\Elementor\Core\DynamicTags\Tag')) {
            return;
        }

        // Registrar grupo personalizado
        \Elementor\Plugin::$instance->dynamic_tags->register_group('youtube-video', array(
            'title' => 'YouTube Video'
        ));

        // Cargar y registrar tags personalizados
        $youtube_url_tag = plugin_dir_path(__FILE__) . 'elementor-tags/youtube-url-tag.php';
        $youtube_desc_tag = plugin_dir_path(__FILE__) . 'elementor-tags/youtube-descripcion-tag.php';

        if (file_exists($youtube_url_tag)) {
            require_once($youtube_url_tag);
            $dynamic_tags->register(new \YouTube_URL_Tag());
        }

        if (file_exists($youtube_desc_tag)) {
            require_once($youtube_desc_tag);
            $dynamic_tags->register(new \YouTube_Descripcion_Tag());
        }
    }

    public function add_admin_pages() {
        // Menú principal
        add_menu_page(
            'YouTube Sync', 
            'YouTube Sync', 
            'manage_options', 
            'youtube-sync', 
            array($this, 'sync_page_html'), 
            'dashicons-video-alt3',
            25
        );
        
        // Submenú: Sincronizar
        add_submenu_page(
            'youtube-sync',
            'Sincronizar Videos',
            'Sincronizar',
            'manage_options',
            'youtube-sync',
            array($this, 'sync_page_html')
        );
        
        // Submenú: Todos los Videos
        add_submenu_page(
            'youtube-sync',
            'Todos los Videos',
            'Todos los Videos',
            'manage_options',
            'edit.php?post_type=video'
        );
        
        // Submenú: Agregar Nuevo
        add_submenu_page(
            'youtube-sync',
            'Agregar Video',
            'Agregar Nuevo',
            'manage_options',
            'post-new.php?post_type=video'
        );
        
        // Submenú: Ciudades (Taxonomía)
        add_submenu_page(
            'youtube-sync',
            'Ciudades',
            'Ciudades',
            'manage_options',
            'edit-tags.php?taxonomy=ciudad&post_type=video'
        );
        
        // Submenú: Configuración
        add_submenu_page(
            'youtube-sync',
            'Configuración',
            'Configuración',
            'manage_options',
            'youtube-sync-settings',
            array($this, 'settings_page_html')
        );
    }
    
    public function set_parent_menu($parent_file) {
        global $submenu_file, $current_screen;
        
        if ($current_screen->post_type == 'video') {
            $submenu_file = 'edit.php?post_type=video';
            $parent_file = 'youtube-sync';
        }
        
        return $parent_file;
    }
    
    public function register_settings() {
        register_setting('ytvs_settings_group', 'ytvs_api_key');
        register_setting('ytvs_settings_group', 'ytvs_channel_id');
        register_setting('ytvs_settings_group', 'ytvs_filter_keywords');
        
        add_settings_section(
            'ytvs_main_section',
            'Configuración de YouTube API',
            array($this, 'settings_section_callback'),
            'youtube-sync-settings'
        );
        
        add_settings_field(
            'ytvs_api_key',
            'API Key de YouTube',
            array($this, 'api_key_field_callback'),
            'youtube-sync-settings',
            'ytvs_main_section'
        );
        
        add_settings_field(
            'ytvs_channel_id',
            'Channel ID',
            array($this, 'channel_id_field_callback'),
            'youtube-sync-settings',
            'ytvs_main_section'
        );
        
        add_settings_field(
            'ytvs_filter_keywords',
            'Palabras Clave de Filtrado',
            array($this, 'filter_keywords_field_callback'),
            'youtube-sync-settings',
            'ytvs_main_section'
        );
    }
    
    public function settings_section_callback() {
        echo '<p>Configura tu API Key de YouTube y el Channel ID para sincronizar los videos.</p>';
    }
    
    public function api_key_field_callback() {
        $value = get_option('ytvs_api_key', '');
        echo '<input type="text" name="ytvs_api_key" value="' . esc_attr($value) . '" class="regular-text" placeholder="AIzaSy...">';
        echo '<p class="description">Obtén tu API Key desde <a href="https://console.cloud.google.com/" target="_blank">Google Cloud Console</a></p>';
    }
    
    public function channel_id_field_callback() {
        $value = get_option('ytvs_channel_id', '');
        echo '<input type="text" name="ytvs_channel_id" value="' . esc_attr($value) . '" class="regular-text" placeholder="UCxxxxxxxxxx">';
        echo '<p class="description">ID de tu canal de YouTube (formato: UCxxxxxxxxxx)</p>';
    }
    
    public function filter_keywords_field_callback() {
        $value = get_option('ytvs_filter_keywords', 'formato 916, 9:16, #shorts, #short, vertical');
        echo '<textarea name="ytvs_filter_keywords" rows="3" class="large-text">' . esc_textarea($value) . '</textarea>';
        echo '<p class="description">Videos que contengan estas palabras en el título o descripción serán excluidos. Separa las palabras con comas. Ejemplo: <code>#shorts, vertical, 9:16</code></p>';
    }
    
    public function settings_page_html() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        if (isset($_GET['settings-updated'])) {
            add_settings_error('ytvs_messages', 'ytvs_message', 'Configuración guardada correctamente', 'updated');
        }
        
        settings_errors('ytvs_messages');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h2>Instrucciones de Configuración</h2>
                
                <h3>1. Obtener API Key de YouTube</h3>
                <ol>
                    <li>Ve a <a href="https://console.cloud.google.com/" target="_blank">Google Cloud Console</a></li>
                    <li>Crea un nuevo proyecto o selecciona uno existente</li>
                    <li>Habilita <strong>YouTube Data API v3</strong></li>
                    <li>Ve a "Credenciales" y crea una API Key</li>
                    <li>Copia tu API Key y pégala abajo</li>
                </ol>
                
                <h3>2. Obtener Channel ID</h3>
                <p>Desde tu canal de YouTube, el ID está en la URL:</p>
                <code>youtube.com/channel/<strong>UCkP9R2e_9MyEOXLqwQstClg</strong></code>
                <p>O desde YouTube Studio → Configuración → Canal → Información avanzada</p>
            </div>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('ytvs_settings_group');
                do_settings_sections('youtube-sync-settings');
                submit_button('Guardar Configuración');
                ?>
            </form>
        </div>
        <?php
    }

    public function sync_page_html() {
        $api_key = $this->get_api_key();
        $channel_id = $this->get_channel_id();
        $report = get_option('yt_sync_last_report');
        
        ?>
        <div class="wrap">
            <h1>Sincronización de Videos de YouTube</h1>
            
            <?php if (empty($api_key) || empty($channel_id)): ?>
                <div class="notice notice-warning">
                    <p><strong>⚠️ Configuración incompleta:</strong> Debes configurar tu API Key y Channel ID antes de sincronizar.</p>
                    <p><a href="<?php echo admin_url('admin.php?page=youtube-sync-settings'); ?>" class="button button-primary">Ir a Configuración</a></p>
                </div>
            <?php else: ?>
                <div class="card" style="max-width: 100%; margin-top: 20px; padding: 15px;">
                    <h3>✅ Configuración completa</h3>
                    <p><strong>Channel ID:</strong> <code><?php echo esc_html($channel_id); ?></code></p>
                    <p><strong>API Key:</strong> <code><?php echo esc_html(substr($api_key, 0, 20)) . '...'; ?></code></p>
                    
                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                        <input type="hidden" name="action" value="sync_youtube_manual">
                        <?php wp_nonce_field('youtube_sync_manual'); ?>
                        <?php submit_button('Sincronizar Ahora', 'primary large', 'submit', false); ?>
                    </form>
                    
                    <p class="description">La sincronización automática se ejecuta diariamente a las 01:00 AM</p>
                </div>
            <?php endif; ?>

            <?php if ($report): ?>
                <h2>Resultado de la última sincronización</h2>
                <p>Fecha: <strong><?php echo $report['time']; ?></strong></p>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Estado</th>
                            <th>Título del Video</th>
                            <th>ID de YouTube</th>
                            <th>ID Post WP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report['details'] as $detail): ?>
                            <tr>
                                <td>
                                    <?php 
                                    if ($detail['status'] == 'imported') echo '<span class="dashicons dashicons-yes" style="color:green"></span> Importado';
                                    else echo '<span class="dashicons dashicons-info" style="color:orange"></span> Ya existía';
                                    ?>
                                </td>
                                <td><?php echo esc_html($detail['title']); ?></td>
                                <td><code><?php echo esc_html($detail['yt_id']); ?></code></td>
                                <td><?php echo $detail['wp_id'] ? $detail['wp_id'] : '-'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <p>Total procesados: <?php echo count($report['details']); ?> videos.</p>
            <?php endif; ?>
        </div>
        <?php
    }

    public function manual_sync() {
        check_admin_referer('youtube_sync_manual');
        $this->sync_videos();
        wp_redirect(admin_url('admin.php?page=youtube-sync&sync=success'));
        exit;
    }

    public function sync_videos() {
        $api_key = $this->get_api_key();
        $channel_id = $this->get_channel_id();
        
        // Validar que exista configuración
        if (empty($api_key) || empty($channel_id)) {
            return;
        }
        
        $uploads_id = str_replace('UC', 'UU', $channel_id);
        $url = "https://www.googleapis.com/youtube/v3/playlistItems?part=snippet&maxResults=50&playlistId={$uploads_id}&key={$api_key}";

        $response = wp_remote_get($url);
        if (is_wp_error($response)) return;
        $data = json_decode(wp_remote_retrieve_body($response));

        if (empty($data->items)) return;

        $report_details = array();

        foreach ($data->items as $item) {
            $video_id = $item->snippet->resourceId->videoId;
            $video_title = $item->snippet->title;

            // Evitar duplicados
            $exists = get_posts(array(
                'post_type' => $this->post_type,
                'meta_key' => '_youtube_video_id',
                'meta_value' => $video_id,
                'posts_per_page' => 1,
                'post_status' => 'any'
            ));

            if (!$exists) {
                $details = $this->get_video_details($video_id);
                if (!$details) continue;

                // Filtrar Shorts: detectar por patrones en título y descripción
                $title = strtolower($details->snippet->title);
                $description = strtolower($details->snippet->description);
                
                // Obtener palabras clave desde opciones
                $filter_keywords_string = get_option('ytvs_filter_keywords', 'formato 916, 9:16, #shorts, #short, vertical');
                $short_patterns = array_map('trim', explode(',', strtolower($filter_keywords_string)));
                $is_short = false;
                
                foreach ($short_patterns as $pattern) {
                    if (strpos($title, $pattern) !== false || strpos($description, $pattern) !== false) {
                        $is_short = true;
                        break;
                    }
                }
                
                if ($is_short) {
                    // Es un Short, no importar
                    continue;
                }

                // Convertir la fecha de publicación de YouTube a formato WordPress
                $youtube_date = $details->snippet->publishedAt;
                $post_date = date('Y-m-d H:i:s', strtotime($youtube_date));

                $post_id = wp_insert_post(array(
                    'post_title'   => $details->snippet->title,
                    'post_content' => $details->snippet->description,
                    'post_status'  => 'publish',
                    'post_type'    => $this->post_type,
                    'post_date'    => $post_date,
                    'post_date_gmt' => get_gmt_from_date($post_date),
                ));

                if ($post_id) {
                    update_post_meta($post_id, '_youtube_video_id', $video_id);
                    update_post_meta($post_id, 'youtube_url', "https://www.youtube.com/watch?v=" . $video_id);
                    update_post_meta($post_id, 'descripcion', $details->snippet->description);
                    
                    $thumb = $details->snippet->thumbnails->high->url;
                    $img_id = $this->upload_image($thumb, $post_id);
                    if ($img_id) {
                        update_post_meta($post_id, 'portada', $img_id);
                        set_post_thumbnail($post_id, $img_id);
                    }

                    //$this->assign_cat($post_id, $details->snippet->categoryId);
                    $this->assign_playlist_taxonomy($post_id, $video_id);
                    
                    $report_details[] = array(
                        'status' => 'imported',
                        'title'  => $video_title,
                        'yt_id'  => $video_id,
                        'wp_id'  => $post_id
                    );
                }
            } else {
                $report_details[] = array(
                    'status' => 'exists',
                    'title'  => $video_title,
                    'yt_id'  => $video_id,
                    'wp_id'  => $exists[0]->ID
                );
            }
        }

        // Guardar el reporte
        update_option('yt_sync_last_report', array(
            'time' => current_time('mysql'),
            'details' => $report_details
        ));
    }

    private function get_video_details($id) {
        $api_key = $this->get_api_key();
        $url = "https://www.googleapis.com/youtube/v3/videos?part=snippet&id={$id}&key={$api_key}";
        $res = wp_remote_get($url);
        if (is_wp_error($res)) return false;
        $data = json_decode(wp_remote_retrieve_body($res));
        return $data->items[0] ?? false;
    }

    private function parse_duration_to_seconds($duration) {
        // Convertir formato ISO 8601 (PT1M30S) a segundos
        $interval = new DateInterval($duration);
        return ($interval->h * 3600) + ($interval->i * 60) + $interval->s;
    }

    private function upload_image($url, $post_id) {
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $id = media_sideload_image($url, $post_id, null, 'id');
        return is_wp_error($id) ? false : $id;
    }

    private function assign_cat($post_id, $yt_cat_id) {
        if (isset($this->category_map[$yt_cat_id])) {
            $slug = $this->category_map[$yt_cat_id];
            $term = get_term_by('slug', $slug, 'category');
            if ($term) wp_set_post_terms($post_id, array($term->term_id), 'category');
        }
    }

    private function assign_playlist_taxonomy($post_id, $video_id) {
        // Buscar playlists del canal y verificar si el video está en cada una
        $channel_playlists_url = "https://www.googleapis.com/youtube/v3/playlists?part=snippet&channelId={$this->channel_id}&maxResults=50&key={$this->api_key}";
        $response = wp_remote_get($channel_playlists_url);
        
        if (is_wp_error($response)) return;
        
        $playlists_data = json_decode(wp_remote_retrieve_body($response));
        
        if (empty($playlists_data->items)) return;
        
        $playlist_terms = array();
        
        // Verificar en cada playlist si el video está incluido
        foreach ($playlists_data->items as $playlist) {
            $playlist_id = $playlist->id;
            $playlist_name = $playlist->snippet->title;
            
            // Verificar si el video está en esta playlist
            $check_url = "https://www.googleapis.com/youtube/v3/playlistItems?part=snippet&playlistId={$playlist_id}&videoId={$video_id}&key={$this->api_key}";
            $check_response = wp_remote_get($check_url);
            
            if (is_wp_error($check_response)) continue;
            
            $check_data = json_decode(wp_remote_retrieve_body($check_response));
            
            // Si el video está en esta playlist, agregar el término
            if (!empty($check_data->items)) {
                // Verificar si el término existe en la taxonomía "ciudad"
                $term = term_exists($playlist_name, 'ciudad');
                
                if (!$term) {
                    // Crear el término si no existe
                    $term = wp_insert_term($playlist_name, 'ciudad');
                    if (is_wp_error($term)) continue;
                }
                
                $term_id = is_array($term) ? $term['term_id'] : $term;
                $playlist_terms[] = (int)$term_id;
            }
        }
        
        // Asignar todos los términos de playlist al post
        if (!empty($playlist_terms)) {
            wp_set_post_terms($post_id, $playlist_terms, 'ciudad', false);
        }
    }

    private function get_playlist_details($playlist_id) {
        $api_key = $this->get_api_key();
        $url = "https://www.googleapis.com/youtube/v3/playlists?part=snippet&id={$playlist_id}&key={$api_key}";
        $res = wp_remote_get($url);
        if (is_wp_error($res)) return false;
        $data = json_decode(wp_remote_retrieve_body($res));
        return $data->items[0] ?? false;
    }
}
new YouTube_Video_Sync();