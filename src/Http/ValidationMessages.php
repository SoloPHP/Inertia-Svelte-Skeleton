<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Turns the failed rules of a ValidationException into the messages a form shows,
 * one per field: the first rule each field failed. solophp/validator reports a
 * rule and its parameters, never a sentence, so the wording is the application's:
 * translate it here.
 */
final class ValidationMessages
{
    private const array MESSAGES = [
        'required' => 'The :field field is required.',
        'filled' => 'The :field field is required.',
        'present' => 'The :field field must be present.',
        'string' => 'The :field must be text.',
        'integer' => 'The :field must be a whole number.',
        'numeric' => 'The :field must be a number.',
        'boolean' => 'The :field must be yes or no.',
        'array' => 'The :field must be a list.',
        'email' => 'The :field must be a valid email address.',
        'date' => 'The :field must be a valid date.',
        'in' => 'The selected :field is not one of the choices.',
        'min' => 'The :field must be at least :0 characters long.',
        'max' => 'The :field may not be longer than :0 characters.',
        'length' => 'The :field must be :0 characters long.',
        'min_value' => 'The :field must be at least :0.',
        'max_value' => 'The :field may not be greater than :0.',
    ];

    /**
     * @param array<string, list<array{rule: string, params?: string[]}>> $errors As ValidationException::getErrors()
     *                                                                        returns them
     * @return array<string, string> Field => message
     */
    public static function for(array $errors): array
    {
        $messages = [];
        foreach ($errors as $field => $failed) {
            if (isset($failed[0])) {
                $messages[$field] = self::message($field, $failed[0]['rule'], $failed[0]['params'] ?? []);
            }
        }

        return $messages;
    }

    /** @param string[] $params */
    private static function message(string $field, string $rule, array $params): string
    {
        $replace = [':field' => str_replace(['_', '.'], ' ', $field)];
        foreach (array_values($params) as $i => $param) {
            $replace[":$i"] = $param;
        }

        return strtr(self::MESSAGES[$rule] ?? 'The :field is not valid.', $replace);
    }
}
