<?php
/*
 * Plugin Name: Cron Daemon
 * Plugin URI: https://cramer.co.za
 * Description: Daemonized cron runner
 * Version: 0.0.1
 * Author: David Cramer
 * Author URI: https://cramer.co.za
 * Text Domain: cron-daemon
 * License: GPL2+
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Constants.
define( 'CRNDMN_PATH', plugin_dir_path( __FILE__ ) );
define( 'CRNDMN_CORE', __FILE__ );
define( 'CRNDMN_URL', plugin_dir_url( __FILE__ ) );

if ( ! version_compare( PHP_VERSION, '5.6', '>=' ) ) {
    if ( is_admin() ) {
        add_action( 'admin_notices', 'cron_daemon_php_ver' );
    }
} else {
    // Includes Cron_Daemon and starts instance.
    include_once CRNDMN_PATH . 'bootstrap.php';
}

function cron_daemon_php_ver() {
    $message = __( 'Cron Daemon requires PHP version 5.6 or later. We strongly recommend PHP 5.6 or later for security and performance reasons.', 'cron-daemon' );
    echo sprintf( '<div id="cron_daemon_error" class="error notice notice-error"><p>%s</p></div>', esc_html( $message ) );
}
