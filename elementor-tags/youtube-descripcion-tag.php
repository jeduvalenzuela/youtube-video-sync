<?php
/**
 * Elementor Dynamic Tag: YouTube Descripción
 * Permite usar la descripción del video como Dynamic Tag en Elementor
 */

if (!defined('ABSPATH')) exit;

class YouTube_Descripcion_Tag extends \Elementor\Core\DynamicTags\Tag {

    public function get_name() {
        return 'youtube-descripcion';
    }

    public function get_title() {
        return 'YouTube Descripción';
    }

    public function get_group() {
        return 'youtube-video';
    }

    public function get_categories() {
        return [
            \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY,
            \Elementor\Modules\DynamicTags\Module::POST_META_CATEGORY
        ];
    }

    protected function register_controls() {
        $this->add_control(
            'format',
            [
                'label' => 'Formato',
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'full',
                'options' => [
                    'full' => 'Texto completo',
                    'excerpt' => 'Extracto (150 caracteres)',
                    'short' => 'Corto (50 caracteres)'
                ]
            ]
        );
    }

    public function render() {
        $descripcion = get_post_meta(get_the_ID(), 'descripcion', true);
        
        if (!$descripcion) {
            return;
        }

        $settings = $this->get_settings();
        $format = isset($settings['format']) ? $settings['format'] : 'full';

        switch ($format) {
            case 'excerpt':
                $descripcion = mb_substr($descripcion, 0, 150) . '...';
                break;
            case 'short':
                $descripcion = mb_substr($descripcion, 0, 50) . '...';
                break;
        }

        echo wp_kses_post($descripcion);
    }

    public function get_content(array $options = []) {
        ob_start();
        $this->render();
        return ob_get_clean();
    }
}
