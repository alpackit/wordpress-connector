<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// The directory separator.
defined('DS') ? DS : define('DS', DIRECTORY_SEPARATOR);


if( !class_exists( 'AlpackitUpdateController' ) ){

    //our update-checker:
    class AlpackitUpdateController{

        public function __construct()
        {
            echo __FILE__;
        }

    }

    //fire once:
    new AlpackitUpdateController();

}