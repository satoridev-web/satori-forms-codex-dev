<?php
/**
 * Plugin Name: SATORI Forms
 * Description: Lightweight configuration-driven form builder for SATORI-managed sites.
 * Version: 1.0.0
 * Author: SATORI
 * Text Domain: satori-forms
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'SATORI_FORMS_VERSION', '1.0.0' );
define( 'SATORI_FORMS_PATH', plugin_dir_path( __FILE__ ) );
define( 'SATORI_FORMS_URL', plugin_dir_url( __FILE__ ) );
define( 'SATORI_FORMS_BASENAME', plugin_basename( __FILE__ ) );

require_once SATORI_FORMS_PATH . 'includes/autoloader.php';

Satori\Forms\Plugin::init();
