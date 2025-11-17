<?php
namespace Satori\Forms\Post_Types;

/**
 * Registers the form post type.
 */
class Form_Post_Type {
    const POST_TYPE = 'form';

    /**
     * Register CPT.
     */
    public static function register() {
        $labels = array(
            'name'               => __( 'Forms', 'satori-forms' ),
            'singular_name'      => __( 'Form', 'satori-forms' ),
            'add_new'            => __( 'Add New', 'satori-forms' ),
            'add_new_item'       => __( 'Add New Form', 'satori-forms' ),
            'edit_item'          => __( 'Edit Form', 'satori-forms' ),
            'new_item'           => __( 'New Form', 'satori-forms' ),
            'view_item'          => __( 'View Form', 'satori-forms' ),
            'search_items'       => __( 'Search Forms', 'satori-forms' ),
            'not_found'          => __( 'No forms found', 'satori-forms' ),
            'not_found_in_trash' => __( 'No forms found in Trash', 'satori-forms' ),
            'all_items'          => __( 'Forms', 'satori-forms' ),
            'menu_name'          => __( 'Forms', 'satori-forms' ),
        );

        $args = array(
            'labels'              => $labels,
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => false,
            'supports'            => array( 'title', 'excerpt' ),
            'has_archive'         => false,
            'rewrite'             => false,
            'capability_type'     => 'post',
            'menu_position'       => 25,
            'show_in_rest'        => false,
            'exclude_from_search' => true,
        );

        register_post_type( static::POST_TYPE, $args );
    }
}
