<?php
namespace Satori\Forms\Shortcodes;

use Satori\Forms\Plugin;
use Satori\Forms\Post_Types\Form_Post_Type;

/**
 * Shortcode handler for [satori_form].
 */
class Form_Shortcode {
    /**
     * Plugin instance.
     *
     * @var Plugin
     */
    protected static $plugin;

    /**
     * Register shortcode.
     *
     * @param Plugin $plugin Plugin instance.
     */
    public static function register( Plugin $plugin ) {
        static::$plugin = $plugin;
        add_shortcode( 'satori_form', array( __CLASS__, 'render' ) );
    }

    /**
     * Render shortcode output.
     *
     * @param array  $atts    Attributes.
     * @param string $content Content.
     *
     * @return string
     */
    public static function render( $atts, $content = '' ) {
        $atts = shortcode_atts( array( 'id' => 0 ), $atts, 'satori_form' );
        $form_id = absint( $atts['id'] );
        if ( ! $form_id ) {
            return '<div class="satori-forms-error">' . esc_html__( 'Form ID missing.', 'satori-forms' ) . '</div>';
        }

        $form = get_post( $form_id );
        if ( ! $form || Form_Post_Type::POST_TYPE !== $form->post_type || 'publish' !== $form->post_status ) {
            return '<div class="satori-forms-error">' . esc_html__( 'Form not found.', 'satori-forms' ) . '</div>';
        }

        $handler = static::$plugin->get_form_handler();
        $result  = $handler ? $handler->get_result( $form_id ) : null;
        $context = array(
            'errors'          => array(),
            'values'          => array(),
            'success'         => false,
            'success_message' => '',
        );

        if ( $result ) {
            $context['errors']          = $result['errors'];
            $context['values']          = $result['values'];
            $context['success']         = $result['success'];
            $context['success_message'] = $result['success'] ? ( $result['message'] ?? '' ) : '';
        }

        $renderer = static::$plugin->get_renderer();
        if ( ! $renderer ) {
            $renderer = new \Satori\Forms\Frontend\Form_Renderer( static::$plugin->get_template_loader() );
        }

        return $renderer->render( $form_id, $context );
    }
}
