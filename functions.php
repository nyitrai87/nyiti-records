<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Front-end styles
 */
function nyiti_records_enqueue_assets()
{
    wp_enqueue_style(
        'nyiti-records-style',
        get_stylesheet_uri(),
        [],
        filemtime(get_stylesheet_directory() . '/style.css')
    );
}
add_action('wp_enqueue_scripts', 'nyiti_records_enqueue_assets');

/**
 * Theme supports
 */
function nyiti_records_theme_setup()
{
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
}
add_action('after_setup_theme', 'nyiti_records_theme_setup');

/**
 * Custom Post Type: record
 */
function nyiti_register_records_cpt()
{
    $labels = [
        'name'          => 'Records',
        'singular_name' => 'Record',
        'add_new'       => 'Add New Record',
        'add_new_item'  => 'Add New Record',
        'edit_item'     => 'Edit Record',
        'new_item'      => 'New Record',
        'view_item'     => 'View Record',
        'search_items'  => 'Search Records',
        'not_found'     => 'No records found',
        'menu_name'     => 'Records',
    ];

    $args = [
        'labels'       => $labels,
        'public'       => true,
        'has_archive'  => true,
        'menu_icon'    => 'dashicons-album',
        'supports'     => ['title', 'editor', 'thumbnail', 'custom-fields'],
        'rewrite'      => ['slug' => 'records'],
        'show_in_rest' => true,
    ];

    register_post_type('record', $args);
}
add_action('init', 'nyiti_register_records_cpt');

/**
 * Register meta so Gutenberg can save it via REST
 */
function nyiti_register_record_meta()
{
    $fields = [
        '_nyiti_artist'  => 'string',
        '_nyiti_album'   => 'string',
        '_nyiti_variant' => 'string',
    ];

    foreach ($fields as $key => $type) {
        register_post_meta('record', $key, [
            'type'              => $type,
            'single'            => true,
            'show_in_rest'      => true,
            'sanitize_callback' => 'sanitize_text_field',
            'auth_callback'     => function () {
                return current_user_can('edit_posts');
            },
        ]);
    }
}
add_action('init', 'nyiti_register_record_meta');

/**
 * Build title from meta (helper)
 * Title format: "artist album variant" (space-separated, no dashes)
 */
function nyiti_build_record_title_from_meta($post_id)
{
    $artist  = trim((string) get_post_meta($post_id, '_nyiti_artist', true));
    $album   = trim((string) get_post_meta($post_id, '_nyiti_album', true));
    $variant = trim((string) get_post_meta($post_id, '_nyiti_variant', true));

    $parts = array_filter([$artist, $album, $variant], function ($v) {
        return trim((string) $v) !== '';
    });

    return trim(implode(' ', $parts));
}

/**
 * When Gutenberg saves via REST, set the title in the SAME request
 */
function nyiti_record_rest_pre_insert($prepared_post, WP_REST_Request $request)
{
    if (empty($prepared_post->post_type) || $prepared_post->post_type !== 'record') {
        return $prepared_post;
    }

    $meta = $request->get_param('meta');
    if (!is_array($meta)) {
        return $prepared_post;
    }

    $artist  = isset($meta['_nyiti_artist']) ? trim((string) $meta['_nyiti_artist']) : '';
    $album   = isset($meta['_nyiti_album']) ? trim((string) $meta['_nyiti_album']) : '';
    $variant = isset($meta['_nyiti_variant']) ? trim((string) $meta['_nyiti_variant']) : '';

    if ($artist === '' && $album === '' && $variant === '') {
        return $prepared_post;
    }

    $parts = array_filter([$artist, $album, $variant], function ($v) {
        return trim((string) $v) !== '';
    });

    $title = trim(implode(' ', $parts));

    $prepared_post->post_title = $title;
    $prepared_post->post_name  = sanitize_title($title);

    return $prepared_post;
}
add_filter('rest_pre_insert_record', 'nyiti_record_rest_pre_insert', 10, 2);

/**
 * IMPORTANT FIX:
 * Force-save meta after REST insert/update (this is what your setup is currently failing to do reliably)
 */
function nyiti_record_rest_after_insert($post, WP_REST_Request $request, $creating)
{
    if (!$post || empty($post->ID)) {
        return;
    }

    $meta = $request->get_param('meta');
    if (!is_array($meta)) {
        return;
    }

    $keys = ['_nyiti_artist', '_nyiti_album', '_nyiti_variant'];

    foreach ($keys as $key) {
        if (array_key_exists($key, $meta)) {
            update_post_meta($post->ID, $key, sanitize_text_field((string) $meta[$key]));
        }
    }
}
add_action('rest_after_insert_record', 'nyiti_record_rest_after_insert', 10, 3);

/**
 * Fallback: if anything still creates/updates a record without a title, fix it after save.
 */
function nyiti_record_autotitle_after_insert($post_id, $post, $update, $post_before)
{
    if (wp_is_post_revision($post_id)) return;
    if (!$post || $post->post_type !== 'record') return;
    if (!current_user_can('edit_post', $post_id)) return;

    $title = nyiti_build_record_title_from_meta($post_id);
    if ($title === '') return;

    $current = trim((string) $post->post_title);

    $junk_titles = ['(no title)', 'auto draft', ''];

    if (!in_array(strtolower($current), $junk_titles, true) && $current === $title) {
        return;
    }

    remove_action('wp_after_insert_post', 'nyiti_record_autotitle_after_insert', 20);

    wp_update_post([
        'ID'         => $post_id,
        'post_title' => $title,
        'post_name'  => sanitize_title($title),
    ]);

    add_action('wp_after_insert_post', 'nyiti_record_autotitle_after_insert', 20, 4);
}
add_action('wp_after_insert_post', 'nyiti_record_autotitle_after_insert', 20, 4);

/**
 * Enqueue Gutenberg sidebar panel JS
 */
function nyiti_enqueue_record_editor_assets($hook)
{
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || $screen->post_type !== 'record') {
        return;
    }

    wp_enqueue_script(
        'nyiti-record-editor',
        get_template_directory_uri() . '/assets/js/record-editor.js',
        ['wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data'],
        filemtime(get_template_directory() . '/assets/js/record-editor.js'),
        true
    );
}
add_action('admin_enqueue_scripts', 'nyiti_enqueue_record_editor_assets');
