<?php
/**
 * Utils class for Cron Daemon.
 *
 * @package cron_daemon
 */

namespace Cron_Daemon;

/**
 * Utils Class.
 */
class Utils {

    /**
     * Broadcast a daemon log.
     *
     * @param string $message The message to log.
     * @param mixed  $data    The data to add to the broadcast.
     * @param string $type    The type of log to send.
     */
    static public function log( $message, $data = null, $type = 'general' ) {
        /**
         * Action a broadcast log.
         *
         * @param string $message The message to log.
         * @param mixed  $data    The data to add to the broadcast.
         * @param string $type    The type of log being broadcast.
         */
        do_action( 'cron_daemon_log', $message, $data, $type );

        /**
         * Action for the specific broadcast log.
         *
         * @param string $message The message to log.
         * @param mixed  $data    The data to add to the broadcast.
         */
        do_action( "cron_daemon_log_{$type}", $message, $data );
    }

}
