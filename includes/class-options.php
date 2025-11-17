<?php
namespace Satori\Forms;

/**
 * Simple wrapper for plugin options.
 */
class Options {
    const OPTION_NAME = 'satori_forms_options';

    /**
     * Cached options.
     *
     * @var array
     */
    protected static $options;

    /**
     * Get option value.
     *
     * @param string $key     Key.
     * @param mixed  $default Default.
     *
     * @return mixed
     */
    public static function get( $key = null, $default = null ) {
        if ( null === static::$options ) {
            $stored = get_option( static::OPTION_NAME, array() );
            if ( ! is_array( $stored ) ) {
                $stored = array();
            }

            static::$options = wp_parse_args( $stored, static::defaults() );
        }

        if ( null === $key ) {
            return static::$options;
        }

        return isset( static::$options[ $key ] ) ? static::$options[ $key ] : $default;
    }

    /**
     * Update options.
     *
     * @param array $values Values.
     */
    public static function update( array $values ) {
        $options          = static::get();
        $options          = array_merge( $options, $values );
        static::$options = $options;
        update_option( static::OPTION_NAME, $options );
    }

    /**
     * Default options.
     *
     * @return array
     */
    public static function defaults() {
        return array(
            'default_admin_emails'    => array( get_option( 'admin_email' ) ),
            'default_admin_subject'   => __( 'New submission received', 'satori-forms' ),
            'default_user_subject'    => __( 'Thanks for contacting us', 'satori-forms' ),
            'default_success_message' => __( 'Thank you! Your submission has been received.', 'satori-forms' ),
            'honeypot_enabled'        => true,
            'timestamp_enabled'       => true,
            'min_fill_seconds'        => 3,
            'rate_limit_enabled'      => true,
            'rate_limit_max'          => 5,
            'rate_limit_window'       => HOUR_IN_SECONDS,
            'recaptcha_site_key'      => '',
            'recaptcha_secret_key'    => '',
        );
    }
}
