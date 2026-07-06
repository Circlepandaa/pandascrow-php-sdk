<?php

declare(strict_types=1);

namespace Pandascrow\Exceptions;

class ValidationException extends ApiException
{
    private array $errors = [];

    public function __construct(
        string $message = 'Validation error',
        int $code = 0,
        ?\Throwable $previous = null,
        ?string $requestId = null,
        ?int $statusCode = null,
        ?array $responseData = null
    ) {
        parent::__construct($message, $code, $previous, $requestId, $statusCode, $responseData);

        // Extract validation errors from response data
        if ($responseData && isset($responseData['errors'])) {
            $this->errors = $responseData['errors'];
        }
    }

    /**
     * Get validation errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get errors for a specific field
     */
    public function getFieldErrors(string $field): array
    {
        return $this->errors[$field] ?? [];
    }

    /**
     * Check if a field has errors
     */
    public function hasFieldErrors(string $field): bool
    {
        return isset($this->errors[$field]) && !empty($this->errors[$field]);
    }

    /**
     * Get all error messages as a flat array
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

    /**
     * Get error summary as string
     */
    public function getErrorSummary(): string
    {
        if (empty($this->errors)) {
            return $this->getMessage();
        }

        $summary = [];
        foreach ($this->errors as $field => $errors) {
            $summary[] = $field . ': ' . implode(', ', $errors);
        }
        return implode('; ', $summary);
    }
}
