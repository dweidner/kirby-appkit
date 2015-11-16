<?php

namespace Appkit;

use A;
use C;
use Dir;
use F;
use Obj;
use Server;

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
   * Read the contents of a directory and return valid module definitions.
   *
   * @param   string  $dir         Directory path.
   * @param   boolean $subfolders  Whether to include subfolders.
   * @param   string  $ext         Expected file extension.
   *
   * @return  array
   */
  public function scan( $root, $subfolders = false, $ext = 'php' ) {

    $modules = array();
    $entries = dir::read( $root, array( '.', '..' ) );

    foreach( $entries as $entry ) {
      $dir = $subfolders && is_dir( $root . DS . $entry ) ? $root . DS . $entry : $root;
      if ( ( $ext === f::extension( $entry ) ) && file_exists( $dir . DS . $entry ) ) {
        $modules[] = $dir . DS . $entry;
      }
    }

    return $modules;

  }

  /**
   * Tries to find the given file. Returns a the path to a variation of the
   * file name if it matches the current environment better.
   *
   * @param   string  $file    Name of the file to load (incl. the extension).
   * @return  string
   */
  public function alter( $file ) {

    $path = f::dirname( $file );
    $name = f::name( $file );
    $ext  = f::extension( $file );

    $files = array(
      $file,
      $path . DS . $name . '.' . server::get('SERVER_NAME') . '.' . $ext,
      $path . DS . $name . '.' . server::get('SERVER_ADDR') . '.' . $ext,
    );

    if ( true === c::get('debug') ) {
      $files[] = $path . DS . $name . '.debug.' . $ext;
    }

    $files = array_filter( $files, 'file_exists' );
    return ! empty( $files ) ? a::last( $files ) : $file;

  }

}
