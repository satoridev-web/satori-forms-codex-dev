<?php
namespace Satori\Forms\Admin;

use Satori\Forms\Options;

/**
 * Settings page handler.
 */
class Settings_Page {
    const SLUG = 'satori-forms-settings';

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    /**
     * Register settings.
     */
    public function register_settings() {
        register_setting( 'satori_forms_settings', Options::OPTION_NAME, array( $this, 'sanitize' ) );
    }

    /**
     * Sanitize settings input.
     *
     * @param array $input Raw input.
     *
     * @return array
     */
    public function sanitize( $input ) {
        $defaults = Options::defaults();
        $output   = array();

        foreach ( $defaults as $key => $default ) {
            $value = isset( $input[ $key ] ) ? $input[ $key ] : null;

            switch ( $key ) {
                case 'default_admin_emails':
                    $value = is_array( $value ) ? implode( ',', $value ) : $value;
                    $value = array_filter( array_map( 'sanitize_email', array_map( 'trim', explode( ',', (string) $value ) ) ) );
                    break;
                case 'default_admin_subject':
                case 'default_user_subject':
                case 'default_success_message':
                case 'recaptcha_site_key':
                case 'recaptcha_secret_key':
                    $value = sanitize_text_field( (string) $value );
                    break;
                case 'min_fill_seconds':
                case 'rate_limit_max':
                case 'rate_limit_window':
                    $value = absint( $value );
                    break;
                case 'honeypot_enabled':
                case 'timestamp_enabled':
                case 'rate_limit_enabled':
                    $value = (bool) $value;
                    break;
                default:
                    $value = $value ?? $default;
                    break;
            }

            $output[ $key ] = $value;
        }

        return wp_parse_args( $output, $defaults );
    }

    /**
     * Render settings page.
     */
    public static function render() {
        $options = Options::get();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'SATORI Forms Settings', 'satori-forms' ); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields( 'satori_forms_settings' ); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Default admin emails', 'satori-forms' ); ?></th>
                        <td>
                            <input type="text" name="<?php echo esc_attr( Options::OPTION_NAME ); ?>[default_admin_emails]" value="<?php echo esc_attr( implode( ',', (array) $options['default_admin_emails'] ) ); ?>" class="regular-text" />
                            <p class="description"><?php esc_html_e( 'Comma-separated list used when a form does not specify recipients.', 'satori-forms' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Default admin subject', 'satori-forms' ); ?></th>
                        <td><input type="text" name="<?php echo esc_attr( Options::OPTION_NAME ); ?>[default_admin_subject]" value="<?php echo esc_attr( $options['default_admin_subject'] ); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Default user subject', 'satori-forms' ); ?></th>
                        <td><input type="text" name="<?php echo esc_attr( Options::OPTION_NAME ); ?>[default_user_subject]" value="<?php echo esc_attr( $options['default_user_subject'] ); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Default success message', 'satori-forms' ); ?></th>
                        <td><textarea name="<?php echo esc_attr( Options::OPTION_NAME ); ?>[default_success_message]" rows="4" class="large-text"><?php echo esc_textarea( $options['default_success_message'] ); ?></textarea></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Enable honeypot', 'satori-forms' ); ?></th>
                        <td><label><input type="checkbox" name="<?php echo esc_attr( Options::OPTION_NAME ); ?>[honeypot_enabled]" value="1" <?php checked( $options['honeypot_enabled'] ); ?> /> <?php esc_html_e( 'Add a hidden honeypot field to forms.', 'satori-forms' ); ?></label></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Enable timestamp check', 'satori-forms' ); ?></th>
                        <td><label><input type="checkbox" name="<?php echo esc_attr( Options::OPTION_NAME ); ?>[timestamp_enabled]" value="1" <?php checked( $options['timestamp_enabled'] ); ?> /> <?php esc_html_e( 'Require a minimum fill time.', 'satori-forms' ); ?></label></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Minimum fill seconds', 'satori-forms' ); ?></th>
                        <td><input type="number" min="0" name="<?php echo esc_attr( Options::OPTION_NAME ); ?>[min_fill_seconds]" value="<?php echo esc_attr( $options['min_fill_seconds'] ); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Enable rate limiting', 'satori-forms' ); ?></th>
                        <td><label><input type="checkbox" name="<?php echo esc_attr( Options::OPTION_NAME ); ?>[rate_limit_enabled]" value="1" <?php checked( $options['rate_limit_enabled'] ); ?> /> <?php esc_html_e( 'Limit submissions per IP.', 'satori-forms' ); ?></label></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Max submissions per window', 'satori-forms' ); ?></th>
                        <td><input type="number" min="1" name="<?php echo esc_attr( Options::OPTION_NAME ); ?>[rate_limit_max]" value="<?php echo esc_attr( $options['rate_limit_max'] ); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Rate limit window (seconds)', 'satori-forms' ); ?></th>
                        <td><input type="number" min="60" step="60" name="<?php echo esc_attr( Options::OPTION_NAME ); ?>[rate_limit_window]" value="<?php echo esc_attr( $options['rate_limit_window'] ); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'reCAPTCHA site key', 'satori-forms' ); ?></th>
                        <td><input type="text" name="<?php echo esc_attr( Options::OPTION_NAME ); ?>[recaptcha_site_key]" value="<?php echo esc_attr( $options['recaptcha_site_key'] ); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'reCAPTCHA secret key', 'satori-forms' ); ?></th>
                        <td><input type="text" name="<?php echo esc_attr( Options::OPTION_NAME ); ?>[recaptcha_secret_key]" value="<?php echo esc_attr( $options['recaptcha_secret_key'] ); ?>" class="regular-text" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}
