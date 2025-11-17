<?php
namespace Satori\Forms\Post_Types;

/**
 * Registers the form submission post type.
 */
class Form_Submission_Post_Type {
    const POST_TYPE = 'form_submission';

    /**
     * Register CPT.
     */
    public static function register() {
        $labels = array(
            'name'               => __( 'Form Submissions', 'satori-forms' ),
            'singular_name'      => __( 'Form Submission', 'satori-forms' ),
            'add_new'            => __( 'Add New', 'satori-forms' ),
            'add_new_item'       => __( 'Add Submission', 'satori-forms' ),
            'edit_item'          => __( 'View Submission', 'satori-forms' ),
            'new_item'           => __( 'New Submission', 'satori-forms' ),
            'view_item'          => __( 'View Submission', 'satori-forms' ),
            'search_items'       => __( 'Search Submissions', 'satori-forms' ),
            'not_found'          => __( 'No submissions found', 'satori-forms' ),
            'not_found_in_trash' => __( 'No submissions found in Trash', 'satori-forms' ),
            'all_items'          => __( 'Submissions', 'satori-forms' ),
            'menu_name'          => __( 'Submissions', 'satori-forms' ),
        );

        $args = array(
            'labels'              => $labels,
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => false,
            'supports'            => array( 'title' ),
            'has_archive'         => false,
            'rewrite'             => false,
            'capability_type'     => 'post',
            'menu_position'       => 26,
            'show_in_rest'        => false,
            'exclude_from_search' => true,
        );

        register_post_type( static::POST_TYPE, $args );
    }
}
