<?php

if (! function_exists('format_plate_as_mercosul')) {
    /**
     * Ensures a plate string always follows the Mercosul pattern, converting from older formats when needed.
     */
    function format_plate_as_mercosul(?string $plate): ?string
    {
        if ($plate === null) {
            return null;
        }

        $sanitized = trim($plate);
        if ($sanitized === '') {
            return '';
        }

        $sanitized = preg_replace('/[^A-Za-z0-9]/', '', $sanitized);
        if ($sanitized === '') {
            return '';
        }

        $sanitized = strtoupper($sanitized);

        $digitToLetter = [
            '0' => 'A',
            '1' => 'B',
            '2' => 'C',
            '3' => 'D',
            '4' => 'E',
            '5' => 'F',
            '6' => 'G',
            '7' => 'H',
            '8' => 'I',
            '9' => 'J',
        ];

        if (preg_match('/^[A-Z]{3}[0-9]{4}$/', $sanitized)) {
            $letters = substr($sanitized, 0, 3);
            $digits = str_split(substr($sanitized, 3));
            return sprintf('%s%s%s%s%s', $letters, $digits[0], $digitToLetter[$digits[1]] ?? $digits[1], $digits[2], $digits[3]);
        }

        return $sanitized;
    }
}
