<?php

namespace Appkit;

use Dir;
use F;
use Obj;

/**
 * Finder
 *
 * A helper class to retrieve the fully qualified path for different
 * application resources.
 *
 * @package  Appkit
 * @author   Daniel Weidner <hallo@danielweidner.de>
 */
class Finder extends Obj {

  /**
   * Constructor.
   *
   * Create a new object instance that maintains application paths.
   *
   * @param  string  $root  Base directory.
   */
  public function __construct( $root ) {

    $this->root = $root;

    $this->app         = $root . DS . 'app';
    $this->assets      = $root . DS . 'assets';

    $this->accounts    = $this->app . DS . 'accounts';
    $this->controllers = $this->app . DS . 'controllers';
    $this->partials    = $this->app . DS . 'partials';
    $this->plugins     = $this->app . DS . 'plugins';
    $this->templates   = $this->app . DS . 'templates';

  }

  /**
   * Read the contents of a directory and return valid extensions.
   *
   * @param   string  $dir  Directory path.
   * @param   string  $ext  Expected file extension.
   *
   * @return  array
   */
  public function extensions( $root, $ext = 'php' ) {

    $extensions = array();
    $entries = dir::read( $root, array( '.', '..' ) );

    foreach( $entries as $entry ) {
      $dir = is_dir( $root . DS . $entry ) ? $root . DS . $entry : $root;
      if ( ( $ext === f::extension( $entry ) ) && file_exists( $dir . DS . $entry ) ) {
        $extensions[] = $dir . DS . $entry;
      }
    }

    return $extensions;

  }

}
