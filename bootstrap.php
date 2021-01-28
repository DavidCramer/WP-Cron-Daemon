<?php
/**
 * Cron Daemon Bootstrap.
 *
 * @package   cron_daemon
 * @author    David Cramer
 * @license   GPL-2.0+
 * @copyright 2021/01/28 David Cramer
 */

/**
 * Activate the plugin core.
 */
function activate_cron_daemon() {
    // Include the core class.
    include_once CRNDMN_PATH . 'classes/class-cron-daemon.php';
    Cron_Daemon::get_instance();
}

add_action( 'plugins_loaded', 'activate_cron_daemon' );
