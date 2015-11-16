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

    $root = dirname( __DIR__ );

    return $this->finder = new Finder( array(
      'root'        => $root,
      'assets'      => $root . DS . '',
      'accounts'    => $root . DS . 'app' . DS . 'accounts',
      'controllers' => $root . DS . 'app' . DS . 'controllers',
      'partials'    => $root . DS . 'app' . DS . 'partials',
      'plugins'     => $root . DS . 'app' . DS . 'plugins',
      'routes'      => $root . DS . 'app' . DS . 'routes',
      'views'       => $root . DS . 'app' . DS . 'views',
      'storage'     => $root . DS . 'storage',
    ) );

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

    $files = $this->finder()->scan( $path, true );

    foreach ( $files as $file ) {
      include_once $file;
    }

    return true;

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

    return $this->router = new Router();

  }

  /**
   * Load and register routes from the app directory.
   *
   * @return  array
   */
  public function routes() {

    if ( ! is_null( $this->router ) ) {
      return $this->router->routes();
    }

    $dir    = $this->finder()->routes();
    $files  = $this->finder()->scan( $dir );
    $router = $this->router();

    foreach( $files as $file ) {
      $route = include_once $file;
      if ( is_array( $route ) && array_key_exists( 'pattern', $route ) ) {
        $router->register( $route['pattern'], $route );
      }
    }

    return $routes;

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

    // Run application configuration
    $this->configure();

    // Register routes defined within the routes directory of the app
    $this->routes();

    // Register default route for the index page
    $this->router()->register( '/', c::get('route.index', array( 'view' => 'home' ) ) );

    // Load application plugins
    $this->plugins();

    // Determine the currently active route
    $path  = implode( '/', (array) url::fragments( detect::path() ) );
    $route = $this->router()->run( $path );

    // Render the error page if route not found
    if ( empty( $route ) ) {
      return $this->error();
    }

    // Render an application view
    if ( ! empty( $route->view ) ) {

      $query = $route->arguments();
      $content = $this->view( $route->view, array(
        'arguments'  => compact('query'),
        'controller' => $route->controller(),
      ) );

      return new Response( $content, 'html' );

    }

    // Execute the action of the route and return the response
    $response = call( $route->action(), $route->arguments() );

    // Wrap the content in a response object
    if ( false === $response ) {
      return $this->error();
    } else if ( is_a( $response, 'Response' ) ) {
      return $response;
    } else if ( is_string( $response ) ) {
      return new Response( $response, 'html' );
    } else {
      return new Response( $response, 'json' );
    }

  }

  /**
   * Generate an error response.
   *
   * @return  Response
   */
  protected function error() {

    $route = (array) c::get( 'route.error', array( 'view' => 'error' ) );
    $content = false;

    if ( ! empty( $route['view'] ) ) {
      $content = $this->view( a::get( $route, 'view' ), $route );
    } else if ( ! empty( $route['action'] ) ) {
      $args = a::get( $route, 'view', array() );
      $content = call( a::get( $route, 'action' ), $args );
    }

    return new Response( $content, 'html', 404 );

  }

  /**
   * Render a view template.
   *
   * @param   string  $name     Name of the view to render.
   * @param   object  $options  Page controller.
   */
  protected function view( $name, $options = array() ) {

    // Construct the template path
    $finder = $this->finder();
    $file = trim( str_replace( '/', DS, $name ), '/' );
    $path = $finder->views . DS . $file . '.php';

    // Template properties
    $scope = a::get( $options, 'arguments', array() );
    $controller = a::get( $options, 'controller', $name );

    // Load the controller from the application directory
    if ( is_string( $controller ) && file_exists( $finder->controllers . DS . $controller . '.php' ) ) {
      $controller = require_once $finder->controllers . DS . $controller . '.php';
    }

    // Complement the template scope using a page controller
    if ( ! empty( $controller ) ) {

      $query = a::get( $scope, 'query', array() );
      $return = call( $controller, $query );

      if ( ! empty( $return ) ) {
        $scope = array_merge( $scope, $return );
      }

    }

    // Load the contents of the template
    return tpl::load( $path, $scope );

  }

}
