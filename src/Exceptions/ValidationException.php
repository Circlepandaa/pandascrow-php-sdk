<?php

declare(strict_types=1);

namespace Pandascrow\Exceptions;

class ValidationException extends ApiException
{
    /** @var array<string, list<string>> */
    private array $errors = [];

    /**
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     * @param string|null $requestId
     * @param int|null $statusCode
     * @param array<mixed>|null $responseData
     */
    public function __construct(
        string $message = 'Validation error',
        int $code = 0,
        ?\Throwable $previous = null,
        ?string $requestId = null,
        ?int $statusCode = null,
        ?array $responseData = null
    ) {
        parent::__construct($message, $code, $previous, $requestId, $statusCode, $responseData);

        if (is_array($responseData) && isset($responseData['errors']) && is_array($responseData['errors'])) {
            /** @var array<string, list<string>> $errors */
            $errors = $responseData['errors'];
            $this->errors = $errors;
        }
    }

    /**
     * @return array<string, list<string>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @param string $field
     * @return list<string>
     */
    public function getFieldErrors(string $field): array
    {
        return $this->errors[$field] ?? [];
    }

    public function hasFieldErrors(string $field): bool
    {
        return isset($this->errors[$field]) && $this->errors[$field] !== [];
    }

    /**
     * @return list<string>
     */
    public function getAllErrorMessages(): array
    {
        $messages = [];
        foreach ($this->errors as $field => $fieldErrors) {
            foreach ($fieldErrors as $error) {
                $messages[] = $field . ': ' . $error;
            }
        }
        return $messages;
    }

    public function getErrorSummary(): string
    {
        if ($this->errors === []) {
            return $this->getMessage();
        }

        $summary = [];
        foreach ($this->errors as $field => $errors) {
            $summary[] = $field . ': ' . implode(', ', $errors);
        }
        return implode('; ', $summary);
    }
}
