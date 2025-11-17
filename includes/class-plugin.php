<?php
namespace Satori\Forms;

use Satori\Forms\Admin\Admin_Menu;
use Satori\Forms\Admin\Settings_Page;
use Satori\Forms\Frontend\Form_Handler;
use Satori\Forms\Frontend\Form_Renderer;
use Satori\Forms\Meta\Form_Meta;
use Satori\Forms\Meta\Submission_Meta;
use Satori\Forms\Post_Types\Form_Post_Type;
use Satori\Forms\Post_Types\Form_Submission_Post_Type;
use Satori\Forms\Shortcodes\Form_Shortcode;
use Satori\Forms\Templates\Template_Loader;

/**
 * Main plugin bootstrap class.
 */
class Plugin {
    /**
     * Singleton instance.
     *
     * @var Plugin
     */
    protected static $instance;

    /**
     * Template loader instance.
     *
     * @var Template_Loader
     */
    protected $template_loader;

    /**
     * Form renderer.
     *
     * @var Form_Renderer
     */
    protected $renderer;

    /**
     * Form handler.
     *
     * @var Form_Handler
     */
    protected $form_handler;

    /**
     * Initialise plugin.
     *
     * @return Plugin
     */
    public static function init() {
        if ( null === static::$instance ) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Constructor.
     */
    protected function __construct() {
        add_action( 'init', array( $this, 'register_post_types' ) );
        add_action( 'init', array( $this, 'register_shortcodes' ) );
        add_action( 'init', array( $this, 'bootstrap_handler' ), 20 );
        add_action( 'wp_enqueue_scripts', array( $this, 'register_frontend_assets' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'register_admin_assets' ) );

        $this->template_loader = new Template_Loader();

        new Form_Meta();
        new Submission_Meta();
        new Admin_Menu();
        new Settings_Page();
    }

    /**
     * Register post types.
     */
    public function register_post_types() {
        Form_Post_Type::register();
        Form_Submission_Post_Type::register();
    }

    /**
     * Register shortcode.
     */
    public function register_shortcodes() {
        Form_Shortcode::register( $this );
    }

    /**
     * Bootstrap form handler.
     */
    public function bootstrap_handler() {
        $this->renderer     = new Form_Renderer( $this->template_loader );
        $this->form_handler = new Form_Handler( $this->renderer );
    }

    /**
     * Register frontend assets.
     */
    public function register_frontend_assets() {
        wp_register_style( 'satori-forms', SATORI_FORMS_URL . 'assets/css/satori-forms.css', array(), SATORI_FORMS_VERSION );
        wp_register_script( 'satori-forms', SATORI_FORMS_URL . 'assets/js/satori-forms.js', array( 'jquery' ), SATORI_FORMS_VERSION, true );
    }

    /**
     * Register admin assets.
     */
    public function register_admin_assets() {
        wp_register_style( 'satori-forms-admin', SATORI_FORMS_URL . 'assets/css/satori-forms.css', array(), SATORI_FORMS_VERSION );
    }

    /**
     * Get template loader.
     *
     * @return Template_Loader
     */
    public function get_template_loader() {
        return $this->template_loader;
    }

    /**
     * Get renderer.
     *
     * @return Form_Renderer
     */
    public function get_renderer() {
        return $this->renderer;
    }

    /**
     * Get form handler.
     *
     * @return Form_Handler
     */
    public function get_form_handler() {
        return $this->form_handler;
    }
}
