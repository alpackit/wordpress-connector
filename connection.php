<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Alpackits update-checker for 57bf2bc37a5f0:
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
    protected $location = '/packit/connection.php';


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
     * Alpackit license token
     *
     * @var string
     */
    protected $license;


    /**
     * Constructor
     */
    public function __construct()
    {
        //get the plugin slug:
        $this->slug = str_replace( $this->location, '', plugin_basename( __FILE__ ) );
        $this->pluginSlug = $this->makePluginSlug();

        $this->license = get_option( self::UUID.'.license', '' );

        if( self::METHOD == 'dev' ){

            set_site_transient( 'update_plugins', null );
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
        //Add the filter for plugin-update checks
        // ( this only runs when the transient isn't set )
        // add_filter( 'pre_set_site_transient_update_plugins', array( &$this, 'check' ), 100, 1 );
        add_filter( 'site_transient_update_plugins', array( &$this, 'check' ), 100, 1 );
        add_filter( 'transient_update_plugins', array( &$this, 'check' ), 100, 1 );

        // Take over the Plugin info screen
        add_filter('plugins_api', array( &$this, 'info' ), 10, 3);

    }

    /**
     * Run plugin checks, on the update_plugin filter
     *
     * @return array
     */
    public function check( $data )
    {
        global $wp_version;

        if ( !isset( $data ) || empty( $data->checked ) )
            return $data;

        //make a remote call:
        $response = wp_remote_get( $this->getUrl() );

        //no error-headers found:
        if( !is_wp_error( $response ) && ( $response['response']['code'] == 200 ) ){

            //body is a json:
            $response = json_decode( $response['body'] );

            //json isn't empty:
            if( is_object( $response ) && !empty( $response ) ){ // Feed the update data into WP updater

                //build the update object
                $update = new stdClass();
                $update->slug = $this->slug;
                $update->plugin = $this->pluginSlug;
                $update->new_version = $response->version;
                $update->url = self::BASE_URL.self::UUID;
                $update->package = $response->download;

                //pass the update object
                $data->response[  $this->pluginSlug ] = $update;
            }

        }else{

            //throw an error
            echo '<pre>';
                print_r( $response );
            echo '</pre>';


        }

        return $data;
    }

    public function getUrl()
    {
        $url = trailingslashit( self::BASE_URL.self::API_VERSION );
        $url .= 'wordpress/license/';
        $url .= $this->license;
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
    /***        Helpers:
    /**********************************************/


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

//fire once:
new Packit_{{PACKIT_CLASS_PREFIX}}_UpdateController();
