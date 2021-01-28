<?php
/**
 * Watcher class for Cron Daemon.
 *
 * @package cron_daemon
 */

namespace Cron_Daemon;

/**
 * Watcher Class.
 */
class Watcher {

    /**
     * Set the Daemon enabled.
     */
    public static function enable() {
        update_option( 'watcher_enabled', true, false );
    }

    /**
     * Set the Daemon disabled.
     */
    public static function disable() {
        if ( self::is_running() ) {
            Utils::log( self::get_active_id() . ': Disabling Watcher' );
        }
        delete_option( 'watcher_enabled' );
        delete_transient( 'watcher_started' );
    }

    /**
     * Get the current active cron daemon ID.
     *
     * @return string
     */
    public static function get_active_id() {
        return get_transient( 'watcher_started' );
    }

    /**
     * Check if cron daemon has been enabled.
     *
     * @return bool
     */
    public static function is_enabled() {
        return ! empty( get_option( 'watcher_enabled' ) );
    }

    /**
     * Check to see if the cron watcher is still running.
     *
     * @return bool
     */
    public static function is_running() {
        return ! empty( get_transient( 'watcher_started' ) );
    }

    /**
     * Attempt to start the cron daemon.
     */
    public static function start() {
        if ( self::is_enabled() ) {
            if ( ! self::is_running() ) {
                $runner_id = uniqid();
                if ( ! self::is_running() ) {
                    set_transient( 'watcher_started', $runner_id, 20 );
                    Utils::log( self::get_active_id() . ': Start Watcher' );
                    self::run( $runner_id );
                } else {
                    Utils::log( self::get_active_id() . ': Already running' );
                }
            }
        }
    }

    /**
     * Init the cron daemon run.
     *
     * @param string|null $runner_id The active daemon ID
     */
    public static function run( $runner_id = null ) {
        if ( is_null( $runner_id ) ) {
            $runner_id = self::get_active_id();
        }
        // Extend running transient.
        if ( ! self::is_enabled() ) {
            Utils::log( self::get_active_id() . ': Stopping Watcher' );

            return;
        }
        // Extend transient.
        set_transient( 'watcher_started', self::get_active_id(), 20 );
        $args = array(
            'timeout'   => 0.01,
            'blocking'  => false,
            /** This filter is documented in wp-includes/class-wp-http-streams.php */
            'sslverify' => apply_filters( 'https_local_ssl_verify', false ),
        );
        $url  = add_query_arg( 'runner_id', $runner_id, rest_url( 'cron/daemon' ) );
        wp_remote_post( $url, $args );
    }

    /**
     * The cron watch runner task. Restarts when ending ofter running to keep active.
     *
     * @param \WP_REST_Request $request
     */
    public static function watch( \WP_REST_Request $request ) {
        $id = $request->get_param( 'runner_id' );
        if ( $id && $id === self::get_active_id() ) {
            // Set the exit method to ensure that the watch continues.
            register_shutdown_function( array( 'Cron_Daemon\\Watcher', 'run' ) );
            sleep( 10 );
            spawn_cron();
        }
    }
}
