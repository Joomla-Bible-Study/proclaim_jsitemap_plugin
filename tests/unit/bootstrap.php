<?php

/**
 * PHPUnit bootstrap for Proclaim JSitemap Plugin unit tests.
 *
 * Loads stub classes for JSitemap and Joomla dependencies, then requires
 * the plugin file under test. This mirrors the Proclaim component's
 * test bootstrap pattern: stubs first, then autoloader, then source.
 *
 * @package  Tests
 */

\error_reporting(E_ALL);

// Define Joomla constants expected by proclaim.php
if (!\defined('_JEXEC')) {
    \define('_JEXEC', 1);
}

if (!\defined('JPATH_SITE')) {
    \define('JPATH_SITE', __DIR__ . '/fixtures');
}

// Load stub classes (order matters: JSitemap stubs define the interface,
// Joomla stubs provide framework classes)
require_once __DIR__ . '/Stubs/JsitemapStubs.php';
require_once __DIR__ . '/Stubs/JoomlaStubs.php';

// Load Composer autoloader if available
$autoloader = \dirname(__DIR__, 2) . '/vendor/autoload.php';

if (\file_exists($autoloader)) {
    require_once $autoloader;
}

// Load the plugin file under test (single unnamespaced class, no PSR-4)
require_once \dirname(__DIR__, 2) . '/proclaim.php';

// Load base test case (not autoloaded — unnamespaced like the plugin)
require_once __DIR__ . '/JMapPluginTestCase.php';
