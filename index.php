<?php

/**
 * Laravel - A PHP Framework For Web Artisans
 *
 * Root entry point allowing Laravel to run when DocumentRoot points to the project root
 * (e.g. shared hosting, dedicated hosting, or local server like Laragon/XAMPP).
 */
$publicPath = __DIR__.'/public';

// Change working directory to public for consistent relative asset & path resolution
if (is_dir($publicPath)) {
    chdir($publicPath);
}

// Ensure public/index.php exists and execute it
if (file_exists($publicPath.'/index.php')) {
    require_once $publicPath.'/index.php';
} else {
    http_response_code(500);
    echo 'Error: public/index.php is missing. Please verify your deployment.';
    exit(1);
}
