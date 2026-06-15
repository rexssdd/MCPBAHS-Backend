<?php

namespace App\Services;

use App\Exceptions\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Facades\Validator as ValidatorFacade;

/**
 * Service for validating API requests with enhanced error handling
 */
class RequestValidationService
{
    /**
     * Validate incoming request data
     * 
     * @param array $data The data to validate
     * @param array $rules The validation rules
     * @param array $messages Custom error messages
     * @return array Validated data
     * @throws ValidationException
     */
    public static function validate(array $data, array $rules, array $messages = []): array
    {
        $validator = ValidatorFacade::make($data, $rules, $messages);

        if ($validator->fails()) {
            throw new ValidationException(
                'Validation failed',
                $validator->errors()->toArray()
            );
        }

        return $validator->validated();
    }

    /**
     * Validate email format and uniqueness
     */
    public static function validateEmail(string $email, ?int $excludeId = null): bool
    {
        $rules = ['email' => 'required|email'];
        if ($excludeId) {
            $rules['email'] .= "|unique:users,email,{$excludeId}";
        } else {
            $rules['email'] .= '|unique:users,email';
        }

        return ValidatorFacade::make(['email' => $email], $rules)->passes();
    }

    /**
     * Validate pagination parameters
     */
    public static function validatePagination(int $page, int $perPage): void
    {
        if ($page < 1) {
            throw new ValidationException('Invalid page number', ['page' => ['Page must be at least 1']]);
        }

        if ($perPage < 1 || $perPage > 100) {
            throw new ValidationException('Invalid per_page value', ['per_page' => ['Per page must be between 1 and 100']]);
        }
    }

    /**
     * Validate date format
     */
    public static function validateDateFormat(string $date, string $format = 'Y-m-d'): bool
    {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    /**
     * Validate date range
     */
    public static function validateDateRange(string $startDate, string $endDate, string $format = 'Y-m-d'): void
    {
        $start = \DateTime::createFromFormat($format, $startDate);
        $end = \DateTime::createFromFormat($format, $endDate);

        if (!$start || !$end || $start > $end) {
            throw new ValidationException('Invalid date range', [
                'date_range' => ['Start date must be before end date'],
            ]);
        }
    }

    /**
     * Validate ID exists in database
     */
    public static function validateResourceExists(string $model, int $id): bool
    {
        $modelClass = "App\\Models\\{$model}";
        return class_exists($modelClass) && $modelClass::find($id) !== null;
    }

    /**
     * Validate that user owns the resource
     */
    public static function validateUserOwnsResource($resource, int $userId): bool
    {
        return isset($resource->user_id) && $resource->user_id === $userId;
    }

    /**
     * Sanitize input string
     */
    public static function sanitizeString(string $input): string
    {
        return trim(strip_tags($input));
    }

    /**
     * Validate enum value
     */
    public static function validateEnum(string $value, array $allowedValues): bool
    {
        return in_array($value, $allowedValues, true);
    }
}
