<?php

declare(strict_types=1);

namespace Pandascrow\Utils;

use Pandascrow\Contracts\LoggerInterface;

class Logger implements LoggerInterface
{
    /** @var list<callable(array<string, mixed>): void> */
    private array $handlers = [];
    /** @var list<callable(array<string, mixed>): array<string, mixed>> */
    private array $processors = [];
    private string $minLevel = 'debug';
    /** @var array<string, int> */
    private array $levelPriorities = [
        'emergency' => 7,
        'alert' => 6,
        'critical' => 5,
        'error' => 4,
        'warning' => 3,
        'notice' => 2,
        'info' => 1,
        'debug' => 0,
    ];

    public function __construct(string $minLevel = 'debug')
    {
        $this->setMinLevel($minLevel);
    }

    /**
     * @param callable(array<string, mixed>): void $handler
     */
    public function addHandler(callable $handler): self
    {
        $this->handlers[] = $handler;
        return $this;
    }

    /**
     * @param callable(array<string, mixed>): array<string, mixed> $processor
     */
    public function addProcessor(callable $processor): self
    {
        $this->processors[] = $processor;
        return $this;
    }

    public function setMinLevel(string $level): self
    {
        if (!isset($this->levelPriorities[$level])) {
            throw new \InvalidArgumentException('Invalid log level: ' . $level);
        }
        $this->minLevel = $level;
        return $this;
    }

    public function emergency(string $message, array $context = []): void
    {
        $this->log('emergency', $message, $context);
    }

    public function alert(string $message, array $context = []): void
    {
        $this->log('alert', $message, $context);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    public function notice(string $message, array $context = []): void
    {
        $this->log('notice', $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    /**
     * @param string $level
     * @param string $message
     * @param array<string, mixed> $context
     */
    public function log($level, $message, array $context = []): void
    {
        $level = (string) $level;
        $message = (string) $message;

        if (!$this->isLevelEnabled($level)) {
            return;
        }

        $record = $this->processRecord($level, $message, $context);

        foreach ($this->handlers as $handler) {
            $handler($record);
        }
    }

    private function isLevelEnabled(string $level): bool
    {
        if (!isset($this->levelPriorities[$level]) || !isset($this->levelPriorities[$this->minLevel])) {
            return false;
        }
        return $this->levelPriorities[$level] >= $this->levelPriorities[$this->minLevel];
    }

    /**
     * @param string $level
     * @param string $message
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function processRecord(string $level, string $message, array $context): array
    {
        $record = [
            'level' => $level,
            'message' => $this->interpolateMessage($message, $context),
            'context' => $this->sanitizeContext($context),
            'timestamp' => date('Y-m-d H:i:s'),
            'channel' => 'pandascrow',
        ];

        foreach ($this->processors as $processor) {
            $record = $processor($record);
        }

        return $record;
    }

    /**
     * @param string $message
     * @param array<string, mixed> $context
     * @return string
     */
    private function interpolateMessage(string $message, array $context): string
    {
        $replace = [];
        foreach ($context as $key => $val) {
            if (!is_array($val) && (!is_object($val) || method_exists($val, '__toString'))) {
                $replace['{' . $key . '}'] = (string) $val;
            }
        }
        return strtr($message, $replace);
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function sanitizeContext(array $context): array
    {
        $sensitiveKeys = ['password', 'secret', 'token', 'api_key', 'authorization'];
        foreach ($sensitiveKeys as $key) {
            if (isset($context[$key])) {
                $context[$key] = '***REDACTED***';
            }
        }
        return $context;
    }

    public function createFileHandler(string $filename, string $level = 'debug'): self
    {
        $this->addHandler(function ($record) use ($filename, $level) {
            if (!$this->isLevelEnabled($level)) {
                return;
            }
            $message = sprintf(
                "[%s] %s: %s %s\n",
                $record['timestamp'],
                strtoupper($record['level']),
                $record['message'],
                json_encode($record['context'])
            );
            file_put_contents($filename, $message, FILE_APPEND);
        });
        return $this;
    }

    public function createConsoleHandler(string $level = 'debug'): self
    {
        $this->addHandler(function ($record) use ($level) {
            if (!$this->isLevelEnabled($level)) {
                return;
            }
            $colors = [
                'error' => "\033[31m",
                'warning' => "\033[33m",
                'info' => "\033[32m",
                'debug' => "\033[36m",
            ];
            $reset = "\033[0m";
            $color = $colors[$record['level']] ?? '';

            echo $color . $record['timestamp'] . ' [' . strtoupper($record['level']) . '] ';
            echo $record['message'];
            if ($record['context'] !== []) {
                echo ' ' . json_encode($record['context']);
            }
            echo $reset . "\n";
        });
        return $this;
    }

    public function getMinLevel(): string
    {
        return $this->minLevel;
    }

    /**
     * @return list<callable(array<string, mixed>): void>
     */
    public function getHandlers(): array
    {
        return $this->handlers;
    }

    /**
     * @return list<callable(array<string, mixed>): array<string, mixed>>
     */
    public function getProcessors(): array
    {
        return $this->processors;
    }

    public function clearHandlers(): self
    {
        $this->handlers = [];
        return $this;
    }

    public function clearProcessors(): self
    {
        $this->processors = [];
        return $this;
    }
}
