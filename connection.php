<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Alpackits update-checker for packit {{PACKIT_CLASS_PREFIX}}
class Packit_{{PACKIT_CLASS_PREFIX}}_UpdateController{

    /**
     * Base url
     *
     * @var string
     */
    const BASE_URL = '{{BASE_URL}}';

    /**
     * API Version
     *
     * @var string
     */
    const API_VERSION = 'v1.0';


    /**
     * Method
     *
     * @var string
     */
    const METHOD = 'dev';


    /**
     * The packits UUID
     *
     * @var string
     */
    const UUID = '{{PACKIT_UUID}}';

    /**
     * Keeps the connection location
     *
     * @var string
     */
    const LOCATION = '/packit/connection.php';


    /**
     * The current plugin slug
     *
     * @var string
     */
    protected $slug;

    /**
     * Plugin slug
     *
     * @var string
     */
    protected $pluginSlug;


    /**
     * Alpackit license
     *
     * @var array
     */
    protected $license;


    /**
     * Constructor
     */
    public function __construct()
    {
        //get the plugin slug:
        $this->slug = str_replace( self::LOCATION, '', plugin_basename( __FILE__ ) );
        $this->pluginSlug = $this->makePluginSlug();

        $this->license = static::getLicense();

        if( self::METHOD == 'dev' ){

            //no plugin transients:
            set_site_transient( 'update_plugins', null );

            //output all result information:
            add_filter( 'plugins_api_result', array( &$this, 'resultInfo' ), 100, 3 );

        }

        if( $this->pluginSlug !== null )
            $this->setEvents();


    }


    /**
     * Set events for updates and license checks
     *
     */
    public function setEvents()
    {

        //only add these events when a valid license is present:
        if( $this->hasValidLicense ){

            //Add the filter for plugin-update checks
            add_filter( 'site_transient_update_plugins', array( &$this, 'checkForUpdate' ), 100, 1 );
            add_filter( 'transient_update_plugins', array( &$this, 'checkForUpdate' ), 100, 1 );

            // Take over the Plugin info screen
            add_filter('plugins_api', array( &$this, 'updateInfo' ), 10, 3);

            // Add custom buttons to the plugin overview-screen

        }else{

            // Create the 'Add license' notifcation

            // Create the 'Add license' button

        }
    }



    /**********************************************/
    /***        Updates:
    /**********************************************/

    /**
     * Run plugin checks, on the update_plugin filter
     *
     * @return array
     */
    public function checkForUpdate( $data )
    {
        global $wp_version;

        if ( !isset( $data ) || empty( $data->checked ) )
            return $data;

        try{

            //make a remote call:
            $response = wp_remote_get( $this->getUrl() );

            //check if this response has errors:
            if( is_wp_error( $response ) || ( $response['response']['code'] == 200 ) )
                throw new Exception( $response->get_error_message() );

            //body is a json:
            $response = json_decode( $response['body'] );

            //check if json wasn't empty:
            if( !is_object( $response ) || empty( $response ) )
                throw new Exception( 'Response couldn\'t be parsed' );

            // Feed the update data into WP updater
            //build the update object
            $update = new stdClass();
            $update->slug = $this->slug;
            $update->plugin = $this->pluginSlug;
            $update->new_version = $response->version;
            $update->url = self::BASE_URL.self::UUID;
            $update->package = $response->download;

            //pass the update object
            $data->response[  $this->pluginSlug ] = $update;


        } catch( Exception $e ) {

            echo $e->getMessage();

        }

        return $data;
    }

    /**
     * Get the alpackit url where we can check the license
     *
     * @return string
     */
    public function getUrl()
    {
        $url = trailingslashit( self::BASE_URL.self::API_VERSION );
        $url .= 'wordpress/license/';
        $url .= $this->license['key'];
        $url .= '/packit/info';

        return $url;

    }


    /**
     * Get version info
     *
     * @param  array $data
     * @return array $data ( altered )
     */
    public function info( $data )
    {
        return $data;
    }


    /**
     * Output results of a plugin api request
     *
     * @return void
     */
    public function resultInfo( $result )
    {
        echo '<pre>';
            print_r( $result );
        echo '</pre>';

        return $result;
    }

    /**********************************************/
    /***        License checks:
    /**********************************************/

    /**
     * Checks if this domain is licensed
     *
     * @param  bool $checkRemote - force a new http request to check
     */
    public static function hasValidLicense( $checkRemote = false ){

        //get the local license:
        $_license = static::getLicense();

        //check if it's available:
        if( static::licenseSet( $_license ) )
            return false;

        //check if it's expired:
        if(
            !isset( $_license[ 'expires' ] ) ||
            $_license[ 'expires' ] > time()
        )
            return false;


        return true;
    }

    /**
     * Checks if the license isn't an empty array and the key is set
     *
     * @return bool
     */
    public static function licenseSet( $_license = array() )
    {
        if( empty( $_license ) || !isset( $_license['key'] ) )
            return false;

        return true;
    }


    /**********************************************/
    /***        Helpers:
    /**********************************************/

    /**
     * Get the license object, if it's saved
     * Defaults to an empty array
     *
     * @return array
     */
    public static function getLicense()
    {
        return get_option( static::UUID.'.license', array() );
    }


    /**
     * Generate the plugin slug
     *
     * @return string
     */
    private function makePluginSlug(){

        $active = get_option( 'active_plugins' );
        foreach( $active as $plugin ){

            if( strpos( $plugin, $this->slug ) !== false )
                return $plugin;

        }

        return null;
    }

}

/**
 * Helper functions:
 */


/**
 * Soft-checks a license
 *
 * @param  string $uuid  - Packit uuid
 * @return bool
 */
if( !function_exists( 'packit_has_license' ) ){

    function packit_has_license( $uuid = null ){

        $class = packit_get_class_name( $uuid );
        return $class::hasValidLicense();
    }
}


/**
 * Does a remote-check to see if a packit has a valid license
 * @param  string $uuid - Packit uuid
 * @return bool
 */
if( !function_exists( 'packit_check_license' ) ){

    function packit_check_license( $uuid = null ){

        $class = packit_get_class_name( $uuid );
        return $class::hasValidLicense( true ); //hard-check
    }
}


/**
 * Return the generated class name
 * @param  string $uuid
 * @return string
 */
if( !function_exists( 'packit_get_class_name' ) ){

    function packit_get_class_name( $uuid = null ){

        if( $uuid == null )
            throw new Exception( 'No uuid given' );

        $prefix = strtolower( str_replace( array( ' ', '-', '_' ), '', $uuid ) );
        return 'Packit_{$prefix}_UpdateController';
    }
}


//fire the class once:
new Packit_{{PACKIT_CLASS_PREFIX}}_UpdateController();



