<?php
namespace Satori\Forms\Frontend;

use Satori\Forms\Meta\Form_Meta;
use Satori\Forms\Meta\Submission_Meta;
use Satori\Forms\Options;
use Satori\Forms\Post_Types\Form_Submission_Post_Type;

/**
 * Handles submissions.
 */
class Form_Handler {
    /**
     * Renderer.
     *
     * @var Form_Renderer
     */
    protected $renderer;

    /**
     * Latest results keyed by form ID.
     *
     * @var array
     */
    protected $results = array();

    /**
     * Constructor.
     *
     * @param Form_Renderer $renderer Renderer.
     */
    public function __construct( Form_Renderer $renderer ) {
        $this->renderer = $renderer;
        add_action( 'init', array( $this, 'maybe_handle_submission' ), 15 );
    }

    /**
     * Handle POST submissions.
     */
    public function maybe_handle_submission() {
        if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            return;
        }

        if ( empty( $_POST['satori_form_id'] ) ) {
            return;
        }

        $form_id = absint( $_POST['satori_form_id'] );
        $result  = $this->process( $form_id );
        $this->results[ $form_id ] = $result;

        if ( $result['success'] && ! empty( $result['redirect'] ) ) {
            wp_safe_redirect( $result['redirect'] );
            exit;
        }
    }

    /**
     * Process submission for a form.
     *
     * @param int $form_id Form ID.
     *
     * @return array
     */
    public function process( $form_id ) {
        $errors = array();
        $values = array();
        $form   = get_post( $form_id );
        if ( ! $form ) {
            return array(
                'success' => false,
                'errors'  => array( 'form' => array( __( 'Form not found.', 'satori-forms' ) ) ),
                'values'  => array(),
            );
        }

        if ( ! isset( $_POST['_satori_forms_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['_satori_forms_nonce'] ), 'satori_form_' . $form_id ) ) {
            return array(
                'success' => false,
                'errors'  => array( 'form' => array( __( 'Security check failed. Please try again.', 'satori-forms' ) ) ),
                'values'  => array(),
            );
        }

        $fields        = get_post_meta( $form_id, Form_Meta::META_FIELDS, true );
        $settings      = get_post_meta( $form_id, Form_Meta::META_SETTINGS, true );
        $notifications = get_post_meta( $form_id, Form_Meta::META_NOTIFICATIONS, true );
        $webhook       = get_post_meta( $form_id, Form_Meta::META_WEBHOOK, true );

        $fields        = is_array( $fields ) ? $fields : array();
        $settings      = is_array( $settings ) ? $settings : array();
        $notifications = is_array( $notifications ) ? $notifications : array();
        $webhook       = is_array( $webhook ) ? $webhook : array();

        $settings = wp_parse_args( $settings, array(
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

        $notifications = wp_parse_args( $notifications, array(
            'admin_emails'  => Options::get( 'default_admin_emails' ),
            'admin_subject' => Options::get( 'default_admin_subject' ),
            'user_reply_to' => '',
            'user_subject'  => Options::get( 'default_user_subject' ),
            'user_template' => __( 'Thanks for contacting us.', 'satori-forms' ),
        ) );

        $webhook = wp_parse_args( $webhook, array(
            'enabled' => false,
            'url'     => '',
            'method'  => 'POST',
            'secret'  => '',
        ) );

        $fields        = apply_filters( 'satori_forms_fields', $fields, $form_id );
        $settings      = apply_filters( 'satori_forms_settings', $settings, $form_id );
        $notifications = apply_filters( 'satori_forms_notifications', $notifications, $form_id );
        $webhook       = apply_filters( 'satori_forms_webhook', $webhook, $form_id );

        do_action( 'satori_forms_before_validate', $form_id, $fields, $settings );

        // Honeypot.
        if ( ! empty( $settings['honeypot_enabled'] ) && ! empty( $_POST['satori_hp'] ) ) {
            $errors['form'][] = __( 'Submission flagged as spam.', 'satori-forms' );
        }

        // Timestamp check.
        if ( ! empty( $settings['timestamp_enabled'] ) && isset( $_POST['satori_ts'] ) ) {
            $loaded = absint( $_POST['satori_ts'] );
            if ( $loaded && ( time() - $loaded ) < absint( $settings['min_fill_seconds'] ) ) {
                $errors['form'][] = __( 'Form submitted too quickly. Please try again.', 'satori-forms' );
            }
        }

        // Rate limiting per IP.
        $ip = $this->get_ip();
        if ( ! empty( $settings['rate_limit']['enabled'] ) ) {
            $key   = 'satori_forms_rate_' . md5( $form_id . '|' . $ip );
            $count = (int) get_transient( $key );
            if ( $count >= absint( $settings['rate_limit']['max'] ) ) {
                $errors['form'][] = __( 'Too many submissions. Please try again later.', 'satori-forms' );
            }
        }

        $payload = array();

        foreach ( $fields as $field ) {
            if ( in_array( $field['type'], array( 'submit' ), true ) ) {
                continue;
            }
            $name  = $field['name'];
            $value = $this->read_field_value( $field );
            $values[ $name ] = $value;

            if ( 'honeypot' === $field['type'] ) {
                continue;
            }

            if ( ! empty( $field['required'] ) && $this->is_empty( $value, $field ) ) {
                $errors[ $name ][] = __( 'This field is required.', 'satori-forms' );
            }

            if ( 'email' === $field['type'] && ! empty( $value ) && ! is_email( $value ) ) {
                $errors[ $name ][] = __( 'Please enter a valid email.', 'satori-forms' );
            }

            if ( 'file' === $field['type'] && isset( $_FILES[ $name ] ) && empty( $errors[ $name ] ) ) {
                $file = $this->handle_upload( $_FILES[ $name ] );
                if ( is_wp_error( $file ) ) {
                    $errors[ $name ][] = $file->get_error_message();
                } else {
                    $value = $file;
                }
            }

            $payload[ $name ] = $value;
        }

        $errors = apply_filters( 'satori_forms_validation_errors', $errors, $form_id, $fields );

        do_action( 'satori_forms_after_validate', $form_id, $fields, $settings, $errors );

        if ( ! empty( $errors ) ) {
            return array(
                'success' => false,
                'errors'  => $errors,
                'values'  => $values,
            );
        }

        $payload = apply_filters( 'satori_forms_submission_payload', $payload, $form_id, $fields );

        $meta = array(
            'ip'       => $ip,
            'referer'  => wp_get_referer(),
            'user_id'  => get_current_user_id(),
            'agent'    => $_SERVER['HTTP_USER_AGENT'] ?? '', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            'datetime' => current_time( 'mysql' ),
        );
        $meta = apply_filters( 'satori_forms_submission_meta', $meta, $form_id );

        do_action( 'satori_forms_before_submit', $form_id, $payload, $meta );

        $submission_id = wp_insert_post( array(
            'post_type'   => Form_Submission_Post_Type::POST_TYPE,
            'post_status' => 'publish',
            'post_title'  => sprintf( /* translators: %s: form title */ __( 'Submission for %s', 'satori-forms' ), get_the_title( $form_id ) ),
            'post_parent' => $form_id,
        ) );

        if ( $submission_id ) {
            update_post_meta( $submission_id, Submission_Meta::META_FORM_ID, $form_id );
            update_post_meta( $submission_id, Submission_Meta::META_PAYLOAD, $payload );
            update_post_meta( $submission_id, Submission_Meta::META_META, $meta );
            update_post_meta( $submission_id, Submission_Meta::META_STATUS, 'received' );
        }

        if ( ! empty( $settings['rate_limit']['enabled'] ) ) {
            $key   = 'satori_forms_rate_' . md5( $form_id . '|' . $ip );
            $count = (int) get_transient( $key );
            set_transient( $key, $count + 1, absint( $settings['rate_limit']['window'] ) );
        }

        $this->send_notifications( $notifications, $payload, $form_id );
        $this->maybe_send_autoresponder( $notifications, $payload );
        $this->maybe_send_webhook( $webhook, $payload, $meta );

        do_action( 'satori_forms_after_submit', $form_id, $submission_id, $payload, $meta );

        return array(
            'success'  => true,
            'errors'   => array(),
            'values'   => array(),
            'message'  => $settings['success_message'],
            'redirect' => $settings['redirect_url'],
        );
    }

    /**
     * Get latest result for a form.
     *
     * @param int $form_id Form ID.
     *
     * @return array|null
     */
    public function get_result( $form_id ) {
        return $this->results[ $form_id ] ?? null;
    }

    /**
     * Read field value from request.
     *
     * @param array $field Field config.
     *
     * @return mixed
     */
    protected function read_field_value( array $field ) {
        $name = $field['name'];
        if ( 'file' === $field['type'] ) {
            return isset( $_FILES[ $name ] ) ? $_FILES[ $name ] : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        }

        $value = isset( $_POST[ $name ] ) ? wp_unslash( $_POST[ $name ] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        if ( is_array( $value ) ) {
            return array_map( 'sanitize_text_field', $value );
        }

        switch ( $field['type'] ) {
            case 'email':
                return sanitize_email( $value );
            case 'textarea':
                return wp_kses_post( $value );
            case 'hidden':
            case 'date':
            case 'text':
            case 'select':
            case 'radio':
            case 'checkbox':
            default:
                return sanitize_text_field( $value );
        }
    }

    /**
     * Determine if value is empty.
     *
     * @param mixed $value Value.
     * @param array $field Field config.
     *
     * @return bool
     */
    protected function is_empty( $value, array $field ) {
        if ( 'file' === $field['type'] ) {
            return empty( $value ) || ( isset( $value['error'] ) && UPLOAD_ERR_NO_FILE === (int) $value['error'] );
        }

        if ( is_array( $value ) ) {
            return empty( array_filter( $value ) );
        }

        return '' === trim( (string) $value );
    }

    /**
     * Handle file uploads.
     *
     * @param array $file File array.
     *
     * @return array|\WP_Error
     */
    protected function handle_upload( $file ) {
        if ( empty( $file ) || ( isset( $file['error'] ) && UPLOAD_ERR_NO_FILE === (int) $file['error'] ) ) {
            return array();
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        $overrides = array( 'test_form' => false );
        $uploaded  = wp_handle_upload( $file, $overrides );
        if ( isset( $uploaded['error'] ) ) {
            return new \WP_Error( 'upload_error', $uploaded['error'] );
        }

        return $uploaded;
    }

    /**
     * Send admin notifications.
     *
     * @param array $notifications Settings.
     * @param array $payload       Payload.
     * @param int   $form_id       Form ID.
     */
    protected function send_notifications( array $notifications, array $payload, $form_id ) {
        $recipients = array_filter( array_map( 'sanitize_email', (array) $notifications['admin_emails'] ) );
        if ( empty( $recipients ) ) {
            return;
        }

        $subject = $notifications['admin_subject'];
        $body    = __( 'A new submission has been received:', 'satori-forms' ) . "\n\n";
        foreach ( $payload as $key => $value ) {
            $body .= sprintf( "%s: %s\n", $key, is_array( $value ) ? wp_json_encode( $value ) : $value );
        }

        $body .= "\n" . sprintf( __( 'Form: %s', 'satori-forms' ), get_the_title( $form_id ) );
        wp_mail( $recipients, $subject, $body );
    }

    /**
     * Send autoresponder if configured.
     *
     * @param array $notifications Notifications.
     * @param array $payload       Payload.
     */
    protected function maybe_send_autoresponder( array $notifications, array $payload ) {
        if ( empty( $notifications['user_reply_to'] ) ) {
            return;
        }

        $field = $notifications['user_reply_to'];
        if ( empty( $payload[ $field ] ) || ! is_email( $payload[ $field ] ) ) {
            return;
        }

        wp_mail( $payload[ $field ], $notifications['user_subject'], $notifications['user_template'] );
    }

    /**
     * Send webhook.
     *
     * @param array $webhook Webhook settings.
     * @param array $payload Payload.
     * @param array $meta    Meta.
     */
    protected function maybe_send_webhook( array $webhook, array $payload, array $meta ) {
        if ( empty( $webhook['enabled'] ) || empty( $webhook['url'] ) ) {
            return;
        }

        $body = array(
            'payload' => $payload,
            'meta'    => $meta,
        );

        if ( ! empty( $webhook['secret'] ) ) {
            $body['signature'] = hash_hmac( 'sha256', wp_json_encode( $payload ), $webhook['secret'] );
        }

        $args = array(
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body'    => wp_json_encode( $body ),
            'method'  => $webhook['method'],
            'timeout' => 10,
        );

        $args = apply_filters( 'satori_forms_webhook_args', $args, $webhook, $payload, $meta );
        wp_remote_request( $webhook['url'], $args );
    }

    /**
     * Determine IP address.
     *
     * @return string
     */
    protected function get_ip() {
        foreach ( array( 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' ) as $key ) {
            if ( ! empty( $_SERVER[ $key ] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                $ip = explode( ',', $_SERVER[ $key ] );
                return sanitize_text_field( trim( $ip[0] ) );
            }
        }

        return '0.0.0.0';
    }
}
