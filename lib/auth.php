<?php

namespace Appkit;

use C;
use S;
use Str;
use Cookie;
use Redirect;
use Response;

use Appkit\User;

/**
 * Auth
 *
 * Allows to restrict access to contents of the application
 * to specific users only.
 *
 * @package  Appkit
 * @author   Daniel Weidner <hallo@danielweidner.de>
 */
class Auth {

  /**
   * Current user.
   *
   * @var  Appkit\User
   */
  protected static $user = null;

  /**
   * Get the currently authenticated user.
   *
   * @return  Appkit\User
   */
  public static function user() {

    if ( ! is_null( static::$user ) ) {
      return static::$user;
    }

    $token = cookie::get('auth');

    if ( empty( $token ) ) {
      return static::$user = false;
    }

    $username = s::get( 'auth.' . $token, false );

    if ( empty( $username ) ) {
      return static::$user = false;
    }

    $user = User::load( $username );

    if ( empty( $user ) || $user->username() !== $username ) {
      return static::$user = false;
    }

    $user->token = $token;
    return static::$user = $user;

  }

  /**
   * Reset and restart the current user session.
   */
  public static function reset() {

    static::$user = null;

    $expires = c::get( 'auth.expires', 60 * 60 * 24 );
    cookie::set( 'auth', str::random(), $expires );

    s::restart();

  }

  /**
   * Force the user to login before continueing.
   *
   * @param   string    $redirect  Path of the page to redirect to.
   * @return  Response
   */
  public static function login( $redirect = '/' ) {

    if ( static::user() ) {
      redirect::to( $redirect );
    }

    static::reset();

    $username = str::lower( get('username') );
    $password = get('password');

    if ( empty( $username ) || empty( $password ) ) {
      return false;
    }

    $user = User::load( $username );

    if ( ! $user ) {
      return response::error('Invalid username or password');
    }

    if ( $username != str::lower( $user->username() ) ) {
      return response::error('Invalid username or password');
    }

    if ( ! static::attempt( $password, $user ) ) {
      return response::error('Invalid username or password');
    }

    $expires = c::get( 'auth.expires', 60 * 60 * 24 );
    cookie::set( 'auth', $user->token = str::random(), $expires );
    s::set( 'auth.' . $user->token, $user->username() );

    redirect::to( url( $redirect ) );

  }

  /**
   * Force a logout.
   *
   * @param   string  $redirect  Path of the page to redirect to after logout.
   */
  public static function logout( $redirect = 'login' ) {

    static::reset();
    redirect::to( url( $redirect ) );

  }

  /**
   * Verify user credientials.
   *
   * @param   string  $password  The password to verify.
   * @param   object  $user      The loaded user instance.
   *
   * @return  boolean
   */
  public static function attempt( $password, $user = null ) {

    if ( is_null( $user ) ) {
      $user = static::user();
    }

    if ( ! $user ) {
      return false;
    }

    if ( empty( $password ) || $user->password() == '' ) {
      return false;
    }

    return sha1( $password ) === $user->password();

  }

  /**
   * Hide a page from unauthorized users.
   *
   * @param   array    $params  Options.
   * @return  boolean
   */
  public static function firewall( $params = array() ) {

    $defaults = array(
      'redirect' => 'login',
      'logout'   => true,
    );
    $options = array_merge( $defaults, $params );
    extract( $options, EXTR_SKIP );

    if ( ! ( $user = static::user() ) ) {
      if ( $logout ) {
        static::reset();
      }
      return redirect::to( $redirect );
    }

    return true;

  }

}
