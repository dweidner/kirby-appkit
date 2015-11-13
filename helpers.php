<?php

/**
 * Access the application core.
 *
 * @return  Appkit\App
 */
function app() {
  return Appkit\App::instance();
}

/**
 * Embeds a partial from the application folder.
 *
 * @param   string   $file
 * @param   mixed    $data
 * @param   boolean  $return
 *
 * @return  string
 */
function partial( $file, $data = array(), $return = false ) {

  if( is_object( $data ) ) {
    $data = array( 'item' => $data );
  }

  $file = app()->finder()->partials() . DS . $file . '.php';
  return tpl::load( $file, $data, $return );

}
