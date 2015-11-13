<?php

/**
 * Bootstrap - Load application dependencies
 *
 * @package  Appkit
 * @author   Daniel Weidner <hallo@danielweidner.de>
 */

// Define frequently used constants
if ( ! defined( 'APPKIT' ) ) define( 'APPKIT', true );
if ( ! defined( 'DS' ) )     define( 'DS', DIRECTORY_SEPARATOR );

// Load external libraries
require __DIR__ . DS . 'vendor' . DS . 'getkirby' . DS . 'toolkit' . DS . 'bootstrap.php';

// Start a session for the current user
s::start();

// Load application classes
load(array(
  'appkit\\app'    => __DIR__ . DS . 'lib' . DS . 'app.php',
  'appkit\\auth'   => __DIR__ . DS . 'lib' . DS . 'auth.php',
  'appkit\\user'   => __DIR__ . DS . 'lib' . DS . 'user.php',
  'appkit\\finder' => __DIR__ . DS . 'lib' . DS . 'finder.php',
));

// Load helper functions
require __DIR__ . DS . 'helpers.php';
