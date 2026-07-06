<?php

declare(strict_types=1);

namespace Pandascrow\Utils;

use Pandascrow\Contracts\LoggerInterface;

/**
 * Null logger that does nothing (useful for disabling logging)
 */
class NullLogger implements LoggerInterface
{
    public function emergency(string $message, array $context = []): void {}
    public function alert(string $message, array $context = []): void {}
    public function critical(string $message, array $context = []): void {}
    public function error(string $message, array $context = []): void {}
    public function warning(string $message, array $context = []): void {}
    public function notice(string $message, array $context = []): void {}
    public function info(string $message, array $context = []): void {}
    public function debug(string $message, array $context = []): void {}
    public function log($level, string $message, array $context = []): void {}
}
