<?php
/**
 * Elementor Dynamic Tag: YouTube URL
 * Permite usar la URL del video como Dynamic Tag en Elementor
 */

if (!defined('ABSPATH')) exit;

class YouTube_URL_Tag extends \Elementor\Core\DynamicTags\Tag {

    public function get_name() {
        return 'youtube-url';
    }

    public function get_title() {
        return 'YouTube URL';
    }

    public function get_group() {
        return 'youtube-video';
    }

    public function get_categories() {
        return [
            \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY,
            \Elementor\Modules\DynamicTags\Module::URL_CATEGORY,
            \Elementor\Modules\DynamicTags\Module::POST_META_CATEGORY
        ];
    }

    public function render() {
        $youtube_url = get_post_meta(get_the_ID(), 'youtube_url', true);
        echo $youtube_url ? esc_url($youtube_url) : '';
    }

    public function get_content(array $options = []) {
        $youtube_url = get_post_meta(get_the_ID(), 'youtube_url', true);
        return $youtube_url ? esc_url($youtube_url) : '';
    }
}
