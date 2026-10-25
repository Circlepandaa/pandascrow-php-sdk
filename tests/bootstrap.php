<?php

// Composer's autoloader when installed; otherwise a tiny PSR-4 loader so the
// suite can run against a globally installed PHPUnit.
if (is_file(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';
}

spl_autoload_register(static function (string $class): void {
    foreach (['Pandascrow\\Tests\\' => __DIR__ . '/', 'Pandascrow\\' => __DIR__ . '/../src/'] as $prefix => $dir) {
        if (strpos($class, $prefix) === 0) {
            $file = $dir . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
            if (is_file($file)) {
                require $file;
            }
            return;
        }
    }
});
