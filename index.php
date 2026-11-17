<?php

/**
 * Laravel - A PHP Framework For Web Artisans
 * Root index.php to run Laravel without accessing /public folder
 */

// Set the public path to the public folder
$publicPath = __DIR__.'/public';

// Change to public directory for serving assets correctly
chdir($publicPath);

// Load the Laravel application
require $publicPath.'/index.php';
