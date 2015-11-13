<?php

namespace Appkit;

use A;
use C;
use Collection;
use Detect;
use Response;
use Router;
use Tpl;
use Url;

use Appkit\Finder;

/**
 * App
 *
 * A collection of functions used to bootstrap the application core.
 * Furthermore an instance of this class serves as an application
 * hub to other core components.
 *
 * @package  Appkit
 * @author   Daniel Weidner <hallo@danielweidner.de>
 */
class App {

  /**
   * Version number of the kit.
   *
   * @var  string
   */
  public static $version = '0.1.0';

  /**
   * Singleton instance.
   *
   * @var  static
   */
  protected static $instance = null;

  /**
   * Component used to retrieve application paths.
   *
   * @var  Appkit\Finder
   */
  protected $finder = null;

  /**
   * Component used to maintain application routes.
   *
   * @var  Router
   */
  protected $router = null;

  /**
   * Collection of application plugins.
   *
   * @var  array
   */
  protected $plugins = null;

  /**
   * Get a singleton instance of the application core.
   *
   * @return  static
   */
  public static function instance() {

    if( ! is_null( static::$instance ) ) {
      return static::$instance;
    }

    return static::$instance = new static();

  }

  /**
   * Get the version of the application toolkit.
   *
   * @return  string
   */
  public static function version() {
    return static::$version;
  }

  /**
   * Constructor.
   *
   * Create a new application instance.
   */
  public function __construct() {

    // Overwrite the reference to existing instances.
    static::$instance = $this;

  }

  /**
   * Get access to the path finder.
   *
   * @return  Appkit\Finder
   */
  public function finder() {

    if ( ! is_null( $this->finder ) ) {
      return $this->finder;
    }

    return $this->finder = new Finder( dirname( __DIR__ ) );

  }

  /**
   * Get access to the component maintaining application routes.
   *
   * @return  Router
   */
  public function router() {

    if ( ! is_null( $this->router ) ) {
      return $this->router;
    }

    $routes = c::get( 'routes', array() );
    return $this->router = new Router( $routes );

  }

  /**
   * Load application configuration.
   */
  public function configure() {

    // Load application configuration from a separate file
    $file = $this->finder()->app() . DS . 'config.php';

    if ( file_exists( $file ) ) {
      include_once( $file );
    }

    // Setup error reporting
    if ( true === c::get( 'debug' ) ) {
      error_reporting( E_ALL );
      ini_set( 'display_errors', 1 );
    } else {
      error_reporting( 0 );
      ini_set( 'display_errors', 0 );
    }

    // Set application timezone
    $timezone = c::get( 'timezone', 'Europe/Berlin' );
    date_default_timezone_set( $timezone );

  }

  /**
   * Load all application plugins.
   *
   * @return  boolean
   */
  public function plugins() {

    $path = $this->finder()->plugins();

    if ( ! is_dir( $path ) ) {
      return false;
    }

    $files = $this->finder()->extensions( $path );

    foreach ( $files as $file ) {
      if ( file_exists( $file ) ) include_once $file;
    }

    return true;

  }

  /**
   * Register a set of application routes.
   *
   * @param   array  $routes  Collection of application routes.
   * @return  array
   */
  public function routes( $routes = null ) {

    if ( is_null( $routes ) ) {
      return $this->routes;
    }

    foreach ( (array) $routes as $pattern => $options ) {
      $this->route( $pattern, $options );
    }

  }

  /**
   * Register a new application route.
   *
   * @param   string  $pattern  Path of the route.
   * @param   array   $params   Route options.
   */
  public function route( $pattern, $params, $optional = array() ) {
    $this->router()->register( $pattern, $params );
  }

  /**
   * Launch the application.
   */
  public function launch() {

    // Run application configuration and load plugins
    $this->configure();
    $this->plugins();

    // Register default routes for the index page
    $this->router()->register( '/', array( 'template' => 'home' ) );

    // Determine the currently active route
    $path  = implode( '/', (array) url::fragments( detect::path() ) );
    $route = $this->router()->run( $path );

    // Render the error page if no route was found
    if ( empty( $route ) ) {
      $status  = 404;
      $content = $this->template( 'error' );
    }

    // Render a page template
    else if ( ! empty( $route->template ) ) {
      $query = $route->arguments();
      $options = array(
        'arguments'  => compact('query'),
        'controller' => $route->controller(),
      );

      $status  = 200;
      $content = $this->template( $route->template, $options );
    }

    // Execute the action of the route
    else {
      $status  = 200;
      $content = call( $route->action(), $route->arguments() );
    }

    // Wrap the content in a response object
    $type = is_string( $content ) ? 'html' : 'json';
    return new Response( $content, $type, $status );

  }

  /**
   * Render a page template.
   *
   * @param   string  $tpl          Template to use.
   * @param   object  $options      Page controller.
   */
  protected function template( $tpl, $options = array() ) {

    // Construct the template path
    $finder = $this->finder();
    $file = trim( str_replace( '/', DS, $tpl ), '/' );
    $path = $finder->templates() . DS . $file . '.php';

    // Template properties
    $args = a::get( $options, 'arguments', array() );
    $controller = a::get( $options, 'controller' );

    // Load the controller from the application directory
    if ( is_string( $controller ) && file_exists( $finder->controllers() . DS . $controller . '.php' ) ) {
      $controller = require_once $finder->controllers() . DS . $controller . '.php';
    }

    // Complement the template scope using a page controller
    if ( ! empty( $controller ) && is_callable( $controller ) ) {

      $query = a::get( $args, 'query', array() );
      $scope = call( $controller, $query );

      if ( ! empty( $scope ) ) {
        $args = array_merge( $args, $scope );
      }

    }

    // Load the contents of the template
    return tpl::load( $path, $args );

  }

}
