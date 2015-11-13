<?php

/**
 * Appkit - A simple application toolkit based on the kirby toolkit
 *
 * @package  Appkit
 * @author   Daniel Weidner <hallo@danielweidner.de>
 */

// Load application dependencies
require __DIR__ . DIRECTORY_SEPARATOR . 'bootstrap.php';

// Creat the application hub
$app = new Appkit\App();

// Turn on the lights
echo $app->launch();
