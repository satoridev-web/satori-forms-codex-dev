<?php
namespace Satori\Forms\Meta;

use Satori\Forms\Options;
use Satori\Forms\Post_Types\Form_Post_Type;

/**
 * Handles form meta boxes and persistence.
 */
class Form_Meta {
    const META_FIELDS        = '_satori_forms_fields';
    const META_SETTINGS      = '_satori_forms_settings';
    const META_NOTIFICATIONS = '_satori_forms_notifications';
    const META_WEBHOOK       = '_satori_forms_webhook';

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        add_action( 'save_post_' . Form_Post_Type::POST_TYPE, array( $this, 'save_meta' ) );
    }

    /**
     * Register meta boxes.
     */
    public function add_meta_boxes() {
        add_meta_box( 'satori-forms-fields', __( 'Form fields', 'satori-forms' ), array( $this, 'render_fields_box' ), Form_Post_Type::POST_TYPE, 'normal', 'high' );
        add_meta_box( 'satori-forms-settings', __( 'Form behaviour & messages', 'satori-forms' ), array( $this, 'render_settings_box' ), Form_Post_Type::POST_TYPE, 'normal', 'default' );
        add_meta_box( 'satori-forms-notifications', __( 'Notifications', 'satori-forms' ), array( $this, 'render_notifications_box' ), Form_Post_Type::POST_TYPE, 'side', 'default' );
        add_meta_box( 'satori-forms-webhook', __( 'Webhook', 'satori-forms' ), array( $this, 'render_webhook_box' ), Form_Post_Type::POST_TYPE, 'side', 'default' );
    }

    /**
     * Render fields meta box.
     *
     * @param \WP_Post $post Post object.
     */
    public function render_fields_box( $post ) {
        wp_nonce_field( 'satori_forms_meta', 'satori_forms_meta_nonce' );
        $fields = get_post_meta( $post->ID, self::META_FIELDS, true );
        $fields = $fields ? wp_json_encode( $fields, JSON_PRETTY_PRINT ) : '';
        echo '<p>' . esc_html__( 'Define fields as JSON. Each field should include id, type, name, label, and other attributes per the spec.', 'satori-forms' ) . '</p>';
        echo '<textarea name="satori_forms_fields" rows="12" style="width:100%;font-family:monospace;">' . esc_textarea( $fields ) . '</textarea>';
    }

    /**
     * Render settings meta box.
     *
     * @param \WP_Post $post Post object.
     */
    public function render_settings_box( $post ) {
        $defaults = Options::defaults();
        $settings = wp_parse_args( get_post_meta( $post->ID, self::META_SETTINGS, true ), array(
            'success_message'   => $defaults['default_success_message'],
            'redirect_url'      => '',
            'honeypot_enabled'  => $defaults['honeypot_enabled'],
            'timestamp_enabled' => $defaults['timestamp_enabled'],
            'min_fill_seconds'  => $defaults['min_fill_seconds'],
            'rate_limit'        => array(
                'enabled' => $defaults['rate_limit_enabled'],
                'max'     => $defaults['rate_limit_max'],
                'window'  => $defaults['rate_limit_window'],
            ),
        ) );
        ?>
        <p>
            <label for="satori_forms_success_message"><strong><?php esc_html_e( 'Success message', 'satori-forms' ); ?></strong></label>
            <textarea name="satori_forms_settings[success_message]" id="satori_forms_success_message" rows="4" style="width:100%;"><?php echo esc_textarea( $settings['success_message'] ); ?></textarea>
        </p>
        <p>
            <label for="satori_forms_redirect_url"><strong><?php esc_html_e( 'Redirect URL (optional)', 'satori-forms' ); ?></strong></label>
            <input type="url" name="satori_forms_settings[redirect_url]" id="satori_forms_redirect_url" value="<?php echo esc_attr( $settings['redirect_url'] ); ?>" class="widefat" />
        </p>
        <p><label><input type="checkbox" name="satori_forms_settings[honeypot_enabled]" value="1" <?php checked( $settings['honeypot_enabled'] ); ?> /> <?php esc_html_e( 'Enable honeypot', 'satori-forms' ); ?></label></p>
        <p><label><input type="checkbox" name="satori_forms_settings[timestamp_enabled]" value="1" <?php checked( $settings['timestamp_enabled'] ); ?> /> <?php esc_html_e( 'Enable timestamp check', 'satori-forms' ); ?></label></p>
        <p>
            <label for="satori_forms_min_fill"><strong><?php esc_html_e( 'Minimum seconds before submission', 'satori-forms' ); ?></strong></label>
            <input type="number" min="0" name="satori_forms_settings[min_fill_seconds]" id="satori_forms_min_fill" value="<?php echo esc_attr( $settings['min_fill_seconds'] ); ?>" />
        </p>
        <p><label><input type="checkbox" name="satori_forms_settings[rate_limit][enabled]" value="1" <?php checked( ! empty( $settings['rate_limit']['enabled'] ) ); ?> /> <?php esc_html_e( 'Enable rate limiting', 'satori-forms' ); ?></label></p>
        <p>
            <label><?php esc_html_e( 'Max submissions', 'satori-forms' ); ?></label>
            <input type="number" min="1" name="satori_forms_settings[rate_limit][max]" value="<?php echo esc_attr( $settings['rate_limit']['max'] ); ?>" style="width:80px;" />
            <label><?php esc_html_e( 'Window (seconds)', 'satori-forms' ); ?></label>
            <input type="number" min="60" step="60" name="satori_forms_settings[rate_limit][window]" value="<?php echo esc_attr( $settings['rate_limit']['window'] ); ?>" style="width:80px;" />
        </p>
        <?php
    }

    /**
     * Render notifications box.
     *
     * @param \WP_Post $post Post object.
     */
    public function render_notifications_box( $post ) {
        $defaults      = Options::defaults();
        $notifications = wp_parse_args( get_post_meta( $post->ID, self::META_NOTIFICATIONS, true ), array(
            'admin_emails'   => $defaults['default_admin_emails'],
            'admin_subject'  => $defaults['default_admin_subject'],
            'user_reply_to'  => '',
            'user_subject'   => $defaults['default_user_subject'],
            'user_template'  => __( 'Thanks for contacting us.', 'satori-forms' ),
        ) );
        ?>
        <p>
            <label for="satori_forms_admin_emails"><strong><?php esc_html_e( 'Admin recipients', 'satori-forms' ); ?></strong></label>
            <input type="text" class="widefat" name="satori_forms_notifications[admin_emails]" id="satori_forms_admin_emails" value="<?php echo esc_attr( implode( ',', (array) $notifications['admin_emails'] ) ); ?>" />
        </p>
        <p>
            <label for="satori_forms_admin_subject"><strong><?php esc_html_e( 'Admin email subject', 'satori-forms' ); ?></strong></label>
            <input type="text" class="widefat" name="satori_forms_notifications[admin_subject]" id="satori_forms_admin_subject" value="<?php echo esc_attr( $notifications['admin_subject'] ); ?>" />
        </p>
        <p>
            <label for="satori_forms_user_reply"><strong><?php esc_html_e( 'User email field (reply-to)', 'satori-forms' ); ?></strong></label>
            <input type="text" class="widefat" name="satori_forms_notifications[user_reply_to]" id="satori_forms_user_reply" value="<?php echo esc_attr( $notifications['user_reply_to'] ); ?>" />
        </p>
        <p>
            <label for="satori_forms_user_subject"><strong><?php esc_html_e( 'User subject', 'satori-forms' ); ?></strong></label>
            <input type="text" class="widefat" name="satori_forms_notifications[user_subject]" id="satori_forms_user_subject" value="<?php echo esc_attr( $notifications['user_subject'] ); ?>" />
        </p>
        <p>
            <label for="satori_forms_user_template"><strong><?php esc_html_e( 'User message', 'satori-forms' ); ?></strong></label>
            <textarea class="widefat" rows="4" name="satori_forms_notifications[user_template]" id="satori_forms_user_template"><?php echo esc_textarea( $notifications['user_template'] ); ?></textarea>
        </p>
        <?php
    }

    /**
     * Render webhook configuration.
     *
     * @param \WP_Post $post Post object.
     */
    public function render_webhook_box( $post ) {
        $webhook = wp_parse_args( get_post_meta( $post->ID, self::META_WEBHOOK, true ), array(
            'enabled' => false,
            'url'     => '',
            'secret'  => '',
            'method'  => 'POST',
        ) );
        ?>
        <p><label><input type="checkbox" name="satori_forms_webhook[enabled]" value="1" <?php checked( $webhook['enabled'] ); ?> /> <?php esc_html_e( 'Send webhook on submission', 'satori-forms' ); ?></label></p>
        <p>
            <label for="satori_forms_webhook_url"><strong><?php esc_html_e( 'Webhook URL', 'satori-forms' ); ?></strong></label>
            <input type="url" class="widefat" name="satori_forms_webhook[url]" id="satori_forms_webhook_url" value="<?php echo esc_attr( $webhook['url'] ); ?>" />
        </p>
        <p>
            <label for="satori_forms_webhook_method"><strong><?php esc_html_e( 'Method', 'satori-forms' ); ?></strong></label>
            <select name="satori_forms_webhook[method]" id="satori_forms_webhook_method" class="widefat">
                <?php foreach ( array( 'POST', 'GET' ) as $method ) : ?>
                    <option value="<?php echo esc_attr( $method ); ?>" <?php selected( $webhook['method'], $method ); ?>><?php echo esc_html( $method ); ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <label for="satori_forms_webhook_secret"><strong><?php esc_html_e( 'Shared secret', 'satori-forms' ); ?></strong></label>
            <input type="text" class="widefat" name="satori_forms_webhook[secret]" id="satori_forms_webhook_secret" value="<?php echo esc_attr( $webhook['secret'] ); ?>" />
        </p>
        <?php
    }

    /**
     * Save meta values.
     *
     * @param int $post_id Post ID.
     */
    public function save_meta( $post_id ) {
        if ( ! isset( $_POST['satori_forms_meta_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['satori_forms_meta_nonce'] ), 'satori_forms_meta' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $fields = isset( $_POST['satori_forms_fields'] ) ? wp_unslash( $_POST['satori_forms_fields'] ) : '';
        $fields = $this->sanitize_fields_json( $fields );
        update_post_meta( $post_id, self::META_FIELDS, $fields );

        $settings = isset( $_POST['satori_forms_settings'] ) ? (array) wp_unslash( $_POST['satori_forms_settings'] ) : array();
        update_post_meta( $post_id, self::META_SETTINGS, $this->sanitize_settings( $settings ) );

        $notifications = isset( $_POST['satori_forms_notifications'] ) ? (array) wp_unslash( $_POST['satori_forms_notifications'] ) : array();
        update_post_meta( $post_id, self::META_NOTIFICATIONS, $this->sanitize_notifications( $notifications ) );

        $webhook = isset( $_POST['satori_forms_webhook'] ) ? (array) wp_unslash( $_POST['satori_forms_webhook'] ) : array();
        update_post_meta( $post_id, self::META_WEBHOOK, $this->sanitize_webhook( $webhook ) );
    }

    /**
     * Sanitize fields JSON payload.
     *
     * @param string $json Raw JSON.
     *
     * @return array
     */
    protected function sanitize_fields_json( $json ) {
        if ( empty( $json ) ) {
            return array();
        }

        $data = json_decode( $json, true );
        if ( ! is_array( $data ) ) {
            return array();
        }

        $sanitized = array();
        foreach ( $data as $field ) {
            if ( empty( $field['name'] ) || empty( $field['type'] ) ) {
                continue;
            }

            $sanitized[] = array(
                'id'             => sanitize_key( $field['id'] ?? uniqid( 'field_' ) ),
                'type'           => sanitize_text_field( $field['type'] ),
                'name'           => sanitize_key( $field['name'] ),
                'label'          => sanitize_text_field( $field['label'] ?? '' ),
                'placeholder'    => sanitize_text_field( $field['placeholder'] ?? '' ),
                'required'       => ! empty( $field['required'] ),
                'options'        => isset( $field['options'] ) ? array_map( 'sanitize_text_field', (array) $field['options'] ) : array(),
                'default'        => isset( $field['default'] ) ? wp_kses_post( $field['default'] ) : '',
                'help_text'      => sanitize_text_field( $field['help_text'] ?? '' ),
                'validation'     => is_array( $field['validation'] ?? '' ) ? array_map( 'sanitize_text_field', (array) $field['validation'] ) : sanitize_text_field( $field['validation'] ?? '' ),
                'css_class'      => sanitize_html_class( $field['css_class'] ?? '' ),
                'wrapper_class'  => sanitize_html_class( $field['wrapper_class'] ?? '' ),
                'attributes'     => isset( $field['attributes'] ) ? array_map( 'sanitize_text_field', (array) $field['attributes'] ) : array(),
            );
        }

        return $sanitized;
    }

    /**
     * Sanitize settings.
     *
     * @param array $settings Settings.
     *
     * @return array
     */
    protected function sanitize_settings( array $settings ) {
        $defaults = Options::defaults();
        return array(
            'success_message'   => sanitize_textarea_field( $settings['success_message'] ?? $defaults['default_success_message'] ),
            'redirect_url'      => esc_url_raw( $settings['redirect_url'] ?? '' ),
            'honeypot_enabled'  => ! empty( $settings['honeypot_enabled'] ),
            'timestamp_enabled' => ! empty( $settings['timestamp_enabled'] ),
            'min_fill_seconds'  => absint( $settings['min_fill_seconds'] ?? $defaults['min_fill_seconds'] ),
            'rate_limit'        => array(
                'enabled' => ! empty( $settings['rate_limit']['enabled'] ),
                'max'     => absint( $settings['rate_limit']['max'] ?? $defaults['rate_limit_max'] ),
                'window'  => absint( $settings['rate_limit']['window'] ?? $defaults['rate_limit_window'] ),
            ),
        );
    }

    /**
     * Sanitize notification settings.
     *
     * @param array $notifications Notifications.
     *
     * @return array
     */
    protected function sanitize_notifications( array $notifications ) {
        $defaults = Options::defaults();
        $emails   = isset( $notifications['admin_emails'] ) ? explode( ',', $notifications['admin_emails'] ) : $defaults['default_admin_emails'];
        $emails   = array_filter( array_map( 'sanitize_email', array_map( 'trim', (array) $emails ) ) );

        return array(
            'admin_emails'  => ! empty( $emails ) ? $emails : $defaults['default_admin_emails'],
            'admin_subject' => sanitize_text_field( $notifications['admin_subject'] ?? $defaults['default_admin_subject'] ),
            'user_reply_to' => sanitize_key( $notifications['user_reply_to'] ?? '' ),
            'user_subject'  => sanitize_text_field( $notifications['user_subject'] ?? $defaults['default_user_subject'] ),
            'user_template' => wp_kses_post( $notifications['user_template'] ?? '' ),
        );
    }

    /**
     * Sanitize webhook.
     *
     * @param array $webhook Webhook config.
     *
     * @return array
     */
    protected function sanitize_webhook( array $webhook ) {
        $method = strtoupper( $webhook['method'] ?? 'POST' );
        if ( ! in_array( $method, array( 'POST', 'GET' ), true ) ) {
            $method = 'POST';
        }

        return array(
            'enabled' => ! empty( $webhook['enabled'] ),
            'url'     => esc_url_raw( $webhook['url'] ?? '' ),
            'secret'  => sanitize_text_field( $webhook['secret'] ?? '' ),
            'method'  => $method,
        );
    }
}
