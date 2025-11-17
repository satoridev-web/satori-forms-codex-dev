<?php
namespace Satori\Forms\Admin;

use Satori\Forms\Admin\Settings_Page;
use Satori\Forms\Post_Types\Form_Post_Type;
use Satori\Forms\Post_Types\Form_Submission_Post_Type;

/**
 * Admin menu registration.
 */
class Admin_Menu {
    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'register_menu' ) );
    }

    /**
     * Register admin menu.
     */
    public function register_menu() {
        add_menu_page(
            __( 'Forms', 'satori-forms' ),
            __( 'Forms', 'satori-forms' ),
            'manage_options',
            'satori-forms',
            array( $this, 'render_welcome' ),
            'dashicons-feedback',
            58
        );

        add_submenu_page(
            'satori-forms',
            __( 'All Forms', 'satori-forms' ),
            __( 'All Forms', 'satori-forms' ),
            'edit_posts',
            'edit.php?post_type=' . Form_Post_Type::POST_TYPE
        );

        add_submenu_page(
            'satori-forms',
            __( 'Add New', 'satori-forms' ),
            __( 'Add New', 'satori-forms' ),
            'edit_posts',
            'post-new.php?post_type=' . Form_Post_Type::POST_TYPE
        );

        add_submenu_page(
            'satori-forms',
            __( 'Submissions', 'satori-forms' ),
            __( 'Submissions', 'satori-forms' ),
            'edit_posts',
            'edit.php?post_type=' . Form_Submission_Post_Type::POST_TYPE
        );

        add_submenu_page(
            'satori-forms',
            __( 'Settings', 'satori-forms' ),
            __( 'Settings', 'satori-forms' ),
            'manage_options',
            Settings_Page::SLUG,
            array( Settings_Page::class, 'render' )
        );
    }

    /**
     * Simple welcome screen.
     */
    public function render_welcome() {
        echo '<div class="wrap"><h1>' . esc_html__( 'SATORI Forms', 'satori-forms' ) . '</h1>';
        echo '<p>' . esc_html__( 'Use the submenu items to manage forms, submissions, and settings.', 'satori-forms' ) . '</p></div>';
    }
}
