<?php

namespace Appkit;

use Obj;

/**
 * User
 *
 * Represents an authenticated user in the application.
 *
 * @package  Appkit
 * @author   Daniel Weidner <hallo@danielweidner.de>
 */
class User extends Obj {

  /**
   * Load a user from the account directory.
   *
   * @param   string  $user  Name of the user to load.
   * @return  static
   */
  public static function load( $user ) {

    $username = str::lower( $user );
    $file = app()->finder()->accounts() . DS . $username . '.php';

    if ( ! file_exists( $file ) ) {
      return false;
    }

    $attrs = file_get_contents( $file );
    $attrs = yaml( $attrs );
    unset( $attrs[0] );

    return new static( $attrs );

  }

}
