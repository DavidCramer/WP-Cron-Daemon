<?php
/**
 * Core class for Cron Daemon.
 *
 * @package cron_daemon
 */

use Cron_Daemon\Watcher;

/**
 * Cron_Daemon Class.
 */
class Cron_Daemon {

    /**
     * The single instance of the class.
     *
     * @var Cron_Daemon
     */
    protected static $instance = null;

    /**
     * Holds the version of the plugin.
     *
     * @var string
     */
    protected $version;

    /**
     * Hold the record of the plugins current version for upgrade.
     *
     * @var string
     */
    const VERSION_KEY = '_cron_daemon_version';

    /**
     * Initiate the cron_daemon object.
     */
    protected function __construct() {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        $plugin        = get_file_data( CRNDMN_CORE, array( 'Version' ), 'plugin' );
        $this->version = array_shift( $plugin );
        spl_autoload_register( array( $this, 'autoload_class' ), true, false );

        // Start hooks.
        $this->setup_hooks();
        if ( Watcher::is_enabled() ) {
            define( 'DISABLE_WP_CRON', true );
        }
    }

    /**
     * Setup and register WordPress hooks.
     */
    protected function setup_hooks() {
        add_action( 'init', array( $this, 'cron_daemon_init' ), PHP_INT_MAX ); // Always the last thing to init.
        add_action( 'admin_init', array( $this, 'admin_init' ) );
        add_action( 'admin_bar_menu', array( $this, 'admin_menu_bar' ), 100 );
        add_action( 'rest_api_init', array( $this, 'init_rest' ) );
    }

    /**
     * Autoloader by Locating and finding classes via folder structure.
     *
     * @param string $class class name to be checked and autoloaded.
     */

    function autoload_class( $class ) {
        $class_location = self::locate_class_file( $class );
        if ( $class_location ) {
            include_once $class_location;
        }
    }

    /**
     * Locates the path to a requested class name.
     *
     * @param string $class The class name to locate.
     *
     * @return string|null
     */
    static public function locate_class_file( $class ) {

        $return = null;
        $parts  = explode( '\\', strtolower( str_replace( '_', '-', $class ) ) );
        $core   = array_shift( $parts );
        $self   = strtolower( str_replace( '_', '-', __CLASS__ ) );
        if ( $core === $self ) {
            $name    = 'class-' . strtolower( array_pop( $parts ) ) . '.php';
            $parts[] = $name;
            $path    = CRNDMN_PATH . 'classes/' . implode( '/', $parts );
            if ( file_exists( $path ) ) {
                $return = $path;
            }
        }

        return $return;
    }

    /**
     * Register the watch endpoint.
     *
     * @param \WP_REST_Server $server The Rest server instance.
     */
    public function init_rest( $server ) {
        register_rest_route(
            'cron',
            'daemon',
            array(
                'methods'  => $server::ALLMETHODS,
                'callback' => array( '\Cron_Daemon\\Watcher', 'watch' ),
            )
        );
    }

    /**
     * Get the plugin version
     */
    public function version() {
        return $this->version;
    }

    /**
     * Check cron_daemon version to allow 3rd party implementations to update or upgrade.
     */
    protected function check_version() {
        $previous_version = get_option( self::VERSION_KEY, 0.0 );
        $new_version      = $this->version();
        if ( version_compare( $previous_version, $new_version, '<' ) ) {
            // Allow for updating.
            do_action( "_cron_daemon_version_upgrade", $previous_version, $new_version );
            // Update version.
            update_option( self::VERSION_KEY, $new_version, true );
        }
    }

    /**
     * Initialise cron_daemon.
     */
    public function cron_daemon_init() {
        // Check version.
        $this->check_version();

        /**
         * Init the settings system
         *
         * @param Cron_Daemon ${slug} The core object.
         */
        do_action( 'cron_daemon_init' );

        Watcher::start();
    }

    /**
     * Hook into admin_init.
     */
    public function admin_init() {
        $enable  = filter_input( INPUT_GET, 'cron_enable', FILTER_SANITIZE_STRING );
        $disable = filter_input( INPUT_GET, 'cron_disable', FILTER_SANITIZE_STRING );
        if ( $enable ) {
            Watcher::enable();
        } elseif ( $disable ) {
            Watcher::disable();
        }
    }

    /**
     * Hook into the menu bar.
     *
     * @param \WP_Admin_Bar $wp_admin_bar The admin bar object.
     */
    public function admin_menu_bar( $wp_admin_bar ) {
        $action = 'cron_enable';
        $icon   = '<span style="color:red;">⦿</span> ';
        $title  = __( 'Enable Cron Daemon', 'cron-daemon' );
        if ( Watcher::is_enabled() ) {
            $action = 'cron_disable';
            $icon   = '<span style="color:green;">⦿</span> ';
            $title  = __( 'Disable Cron Daemon', 'cron-daemon' );
        }

        $current_url = filter_input( INPUT_SERVER, 'REQUEST_URI', FILTER_SANITIZE_URL );
        $url         = add_query_arg( $action, true, $current_url );
        $wp_admin_bar->add_node(
            [
                'id'     => 'cron-daemon',
                'parent' => false,
                'title'  => $icon . $title,
                'href'   => $url,
            ]
        );
    }

    /**
     * Get the instance of the class.
     *
     * @return Cron_Daemon
     */
    public static function get_instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}
