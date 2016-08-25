<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Add this packit to a global array:
$GLOBAL['packits'][ plugin_basename( __FILE__ ) ] = '57bf2bc37a5f0';


// Initiate the class once:
if( !class_exists( 'AlpackitUpdateController' ) ){

    // Alpackits update-checker:
    class AlpackitUpdateController{

        /**
         * Base url
         *
         * @var string
         */
        const BASE_URL = 'http://alpackit.dev';

        /**
         * Keeps the connection location
         *
         * @var string
         */
        protected $location = '/vendors/alpackit/connection.php';

        /**
         * The current plugin slug
         *
         * @var string
         */
        protected $slug;


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
            $this->slug = $this->makeSlug( plugin_basename( __FILE__ ) );
            $this->license = get_option( $this->slug.'-license', '' );

            $this->setEvents();
        }

        /**
         * Set events for updates and license checks
         *
         */
        public function setEvents()
        {

        }

        /**
         * Generate the plugin slug
         *
         * @return string
         */
        private makeSlug(){
            return str_replace( $this->location, '', plugin_basename( __FILE__ ) );
        }

    }

    //fire once:
    new AlpackitUpdateController();

}