<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

function nyiti_records_enqueue_assets() {
    wp_enqueue_style(
        'nyiti-records-style',
        get_stylesheet_uri(),
        [],
        '1.0.0'
    );
}

add_action( 'wp_enqueue_scripts', 'nyiti_records_enqueue_assets' );


function nyiti_records_theme_setup() {

    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );

}

add_action( 'after_setup_theme', 'nyiti_records_theme_setup' );

function nyiti_register_records_cpt() {

    $labels = [
        'name'               => 'Records',
        'singular_name'      => 'Record',
        'add_new'            => 'Add New Record',
        'add_new_item'       => 'Add New Record',
        'edit_item'          => 'Edit Record',
        'new_item'           => 'New Record',
        'view_item'          => 'View Record',
        'search_items'       => 'Search Records',
        'not_found'          => 'No records found',
        'menu_name'          => 'Records',
    ];

    $args = [
        'labels'             => $labels,
        'public'             => true,
        'has_archive'        => true,
        'menu_icon'          => 'dashicons-album',
        'supports'           => ['editor', 'thumbnail'],
        'rewrite'            => ['slug' => 'records'],
        'show_in_rest'       => true,
    ];

    register_post_type( 'record', $args );
}

add_action( 'init', 'nyiti_register_records_cpt' );

function nyiti_record_autotitle_from_meta($post_id, $post, $update) {

    if ( wp_is_post_autosave($post_id) || wp_is_post_revision($post_id) ) {
        return;
    }

    if ( $post->post_type !== 'record' ) {
        return;
    }

    if ( ! current_user_can('edit_post', $post_id) ) {
        return;
    }

    $artist  = get_post_meta($post_id, '_nyiti_artist', true);
    $album   = get_post_meta($post_id, '_nyiti_album', true);

    $artist = is_string($artist) ? trim($artist) : '';
    $album  = is_string($album) ? trim($album) : '';

    if ( $artist === '' && $album === '' ) {
        return;
    }

    $title = ($artist !== '' && $album !== '')
        ? $artist . ' - ' . $album
        : ($artist !== '' ? $artist : $album);

    if ( $post->post_title === $title ) {
        return;
    }

    // Prevent infinite loop
    remove_action('save_post', 'nyiti_record_autotitle_from_meta', 20);

    wp_update_post([
        'ID'         => $post_id,
        'post_title' => $title,
        'post_name'  => sanitize_title($title),
    ]);

    add_action('save_post', 'nyiti_record_autotitle_from_meta', 20, 3);
}
add_action('save_post', 'nyiti_record_autotitle_from_meta', 20, 3);


function nyiti_register_record_meta() {
    $fields = [
        '_nyiti_artist'  => 'string',
        '_nyiti_album'   => 'string',
        '_nyiti_variant' => 'string',
    ];

    foreach ($fields as $key => $type) {
        register_post_meta('record', $key, [
            'type'         => $type,
            'single'       => true,
            'show_in_rest' => true,
            'auth_callback' => function() {
                return current_user_can('edit_posts');
            },
            'sanitize_callback' => 'sanitize_text_field',
        ]);
    }
}
add_action('init', 'nyiti_register_record_meta');

function nyiti_enqueue_record_editor_assets($hook) {
    // Only load on record edit screens
    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== 'record') {
        return;
    }

    wp_enqueue_script(
        'nyiti-record-editor',
        get_template_directory_uri() . '/assets/js/record-editor.js',
        ['wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data'],
        '1.0.0',
        true
    );
}
add_action('admin_enqueue_scripts', 'nyiti_enqueue_record_editor_assets');
