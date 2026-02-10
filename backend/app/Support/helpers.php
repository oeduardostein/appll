<?php

if (! function_exists('normalize_plate')) {
    /**
     * Normalizes a plate string by stripping non-alphanumeric characters and uppercasing.
     *
     * This function does not perform "old -> Mercosul" conversions.
     */
    function normalize_plate(?string $plate): ?string
    {
        if ($plate === null) {
            return null;
        }

        $sanitized = trim($plate);
        if ($sanitized === '') {
            return '';
        }

        $sanitized = preg_replace('/[^A-Za-z0-9]/', '', $sanitized) ?? '';
        if ($sanitized === '') {
            return '';
        }

        return strtoupper($sanitized);
    }
}

if (! function_exists('format_plate_as_mercosul')) {
    /**
     * Backwards-compatible alias. Keeps behavior limited to normalization.
     */
    function format_plate_as_mercosul(?string $plate): ?string
    {
        return normalize_plate($plate);
    }
}
