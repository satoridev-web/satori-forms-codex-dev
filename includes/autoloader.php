<?php
/**
 * Simple PSR-4 style autoloader for the plugin.
 */

namespace Satori\Forms;

if ( ! class_exists( __NAMESPACE__ . '\\Autoloader' ) ) {
    /**
     * Autoload plugin classes.
     */
    class Autoloader {
        /**
         * Register autoloader.
         */
        public static function register() {
            spl_autoload_register( array( __CLASS__, 'autoload' ) );
        }

        /**
         * Load class files.
         *
         * @param string $class Class name.
         */
        public static function autoload( $class ) {
            $prefix   = __NAMESPACE__ . '\\';
            $base_dir = SATORI_FORMS_PATH . 'includes/';

            if ( 0 !== strpos( $class, $prefix ) ) {
                return;
            }

            $relative = substr( $class, strlen( $prefix ) );
            $relative = str_replace( '\\', '/', $relative );
            $segments = explode( '/', $relative );
            $file     = array_pop( $segments );

            $segments = array_map( function( $segment ) {
                return strtolower( str_replace( '_', '-', $segment ) );
            }, $segments );

            $path = $base_dir;
            if ( ! empty( $segments ) ) {
                $path .= implode( '/', $segments ) . '/';
            }

            $file = 'class-' . strtolower( str_replace( '_', '-', $file ) ) . '.php';
            $path .= $file;

            if ( file_exists( $path ) ) {
                require_once $path;
            }
        }
    }
}

Autoloader::register();
