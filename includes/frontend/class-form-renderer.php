<?php
namespace Satori\Forms\Frontend;

use Satori\Forms\Meta\Form_Meta;
use Satori\Forms\Options;
use Satori\Forms\Templates\Template_Loader;

/**
 * Renders forms on the frontend.
 */
class Form_Renderer {
    /**
     * Template loader.
     *
     * @var Template_Loader
     */
    protected $loader;

    /**
     * Constructor.
     *
     * @param Template_Loader $loader Loader.
     */
    public function __construct( Template_Loader $loader ) {
        $this->loader = $loader;
    }

    /**
     * Render form.
     *
     * @param int   $form_id Form ID.
     * @param array $context Additional context (errors, values, success message).
     *
     * @return string
     */
    public function render( $form_id, array $context = array() ) {
        $form = get_post( $form_id );
        if ( ! $form || 'form' !== $form->post_type ) {
            return '<div class="satori-forms-error">' . esc_html__( 'Form unavailable.', 'satori-forms' ) . '</div>';
        }

        $fields   = get_post_meta( $form_id, Form_Meta::META_FIELDS, true );
        $settings = get_post_meta( $form_id, Form_Meta::META_SETTINGS, true );
        $fields   = is_array( $fields ) ? $fields : array();
        $settings = wp_parse_args( is_array( $settings ) ? $settings : array(), array(
            'success_message'   => Options::get( 'default_success_message' ),
            'redirect_url'      => '',
            'honeypot_enabled'  => Options::get( 'honeypot_enabled' ),
            'timestamp_enabled' => Options::get( 'timestamp_enabled' ),
            'min_fill_seconds'  => Options::get( 'min_fill_seconds' ),
            'rate_limit'        => array(
                'enabled' => Options::get( 'rate_limit_enabled' ),
                'max'     => Options::get( 'rate_limit_max' ),
                'window'  => Options::get( 'rate_limit_window' ),
            ),
        ) );

        $fields   = apply_filters( 'satori_forms_fields', $fields, $form_id );
        $settings = apply_filters( 'satori_forms_settings', $settings, $form_id );

        $context = wp_parse_args( $context, array(
            'errors'          => array(),
            'values'          => array(),
            'success'         => false,
            'success_message' => $settings['success_message'],
        ) );

        do_action( 'satori_forms_before_render', $form_id, $fields, $settings, $context );

        wp_enqueue_style( 'satori-forms' );
        wp_enqueue_script( 'satori-forms' );

        $html = $this->loader->render( 'form.php', array(
            'form'            => $form,
            'form_id'         => $form_id,
            'fields'          => $fields,
            'settings'        => $settings,
            'errors'          => $context['errors'],
            'values'          => $context['values'],
            'success'         => $context['success'],
            'success_message' => $context['success_message'],
            'renderer'        => $this,
        ), false );

        do_action( 'satori_forms_after_render', $form_id, $fields, $settings, $context );

        return $html;
    }

    /**
     * Render a field template.
     *
     * @param array $field Field configuration.
     * @param array $values Submitted values.
     * @param array $errors Errors.
     */
    public function render_field( array $field, array $values, array $errors ) {
        $field = apply_filters( 'satori_forms_field', $field );
        $template = 'fields/field-' . sanitize_key( $field['type'] ) . '.php';
        if ( ! $this->loader->locate( $template ) ) {
            $template = 'fields/field-text.php';
        }

        $this->loader->render( $template, array(
            'field'  => $field,
            'value'  => $values[ $field['name'] ] ?? ( $field['default'] ?? '' ),
            'errors' => $this->get_field_errors( $field['name'], $errors ),
        ) );
    }

    /**
     * Render error summary.
     *
     * @param array $errors Errors.
     */
    public function render_errors( array $errors ) {
        if ( empty( $errors ) ) {
            return;
        }

        $this->loader->render( 'parts/errors.php', array( 'errors' => $errors ) );
    }

    /**
     * Render success message.
     *
     * @param string $message Message.
     */
    public function render_success( $message ) {
        $this->loader->render( 'parts/success-message.php', array( 'message' => $message ) );
    }

    /**
     * Get field errors by key.
     *
     * @param string $name Field name.
     * @param array  $errors Errors.
     *
     * @return array
     */
    public function get_field_errors( $name, array $errors ) {
        return isset( $errors[ $name ] ) ? (array) $errors[ $name ] : array();
    }
}
