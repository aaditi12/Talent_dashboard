<?php
// autoload.php
// Simple PSR-4 autoloader for PHPMailer (without Composer)

spl_autoload_register(function ($class) {
    // project-specific namespace prefix
    $prefix = 'PHPMailer\\PHPMailer\\';

    // base directory for the namespace prefix
    $base_dir = __DIR__ . '/vendor/phpmailer/phpmailer/src/';

    // does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // move to the next registered autoloader
        return;
    }

    // get the relative class name
    $relative_class = substr($class, $len);

    // replace namespace separators with directory separators,
    // append with .php
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // if the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});

// Usage: include 'autoload.php'; then use PHPMailer\PHPMailer\PHPMailer;
