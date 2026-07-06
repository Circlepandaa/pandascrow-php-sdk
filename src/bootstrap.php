<?php

declare(strict_types=1);

// Force load PSR Log classes manually in the correct order
$psrLogPath = __DIR__ . '/../vendor/psr/log/src/';

if (!interface_exists('Psr\Log\LoggerInterface')) {
    // Load in the correct order (dependencies first)
    $files = [
        'LoggerInterface.php',      // Interface
        'LogLevel.php',             // Constants
        'LoggerAwareInterface.php', // Interface
        'LoggerTrait.php',          // Trait
        'LoggerAwareTrait.php',     // Trait
        'AbstractLogger.php',       // Abstract class (extends LoggerInterface)
        'NullLogger.php',           // Class (extends AbstractLogger)
        'InvalidArgumentException.php', // Exception
    ];

    foreach ($files as $file) {
        $fullPath = $psrLogPath . $file;
        if (file_exists($fullPath)) {
            require_once $fullPath;
        }
    }
}

// Verify it loaded
if (!interface_exists('Psr\Log\LoggerInterface')) {
    throw new \RuntimeException('Failed to load PSR Log classes');
}

if (!class_exists('Psr\Log\NullLogger')) {
    throw new \RuntimeException('Failed to load NullLogger');
}
