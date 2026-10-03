<?php

namespace App\Licensing;

final class ProductionDomain
{
    public static function normalize(string $value): ?string
    {
        $domain = strtolower(trim($value));

        if ($domain === '' || strlen($domain) > 253) {
            return null;
        }

        if (
            str_contains($domain, '://') ||
            str_contains($domain, '/') ||
            str_contains($domain, ':') ||
            str_contains($domain, '?') ||
            str_contains($domain, '#') ||
            str_contains($domain, '*') ||
            str_contains($domain, '_') ||
            str_contains($domain, ' ') ||
            str_ends_with($domain, '.')
        ) {
            return null;
        }

        if (filter_var($domain, FILTER_VALIDATE_IP) !== false) {
            return null;
        }

        if (! str_contains($domain, '.')) {
            return null;
        }

        foreach (explode('.', $domain) as $label) {
            if (
                $label === '' ||
                strlen($label) > 63 ||
                preg_match('/^[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/', $label) !== 1
            ) {
                return null;
            }
        }

        return $domain;
    }
}
