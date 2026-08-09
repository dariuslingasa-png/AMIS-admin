<?php

namespace App\Services\Payment;

class PaymentReceiptParser
{
    /**
     * Normalize raw text values into standardized database strings.
     */
    public static function normalizeAmount(?string $rawAmount): ?float
    {
        if (empty($rawAmount)) {
            return null;
        }

        $cleaned = preg_replace('/[^0-9\.]/', '', str_replace(',', '', $rawAmount));
        if (is_numeric($cleaned)) {
            return (float) $cleaned;
        }

        return null;
    }

    public static function normalizeReference(?string $rawRef): ?string
    {
        if (empty($rawRef)) {
            return null;
        }

        $cleaned = preg_replace('/[^A-Za-z0-9]/', '', $rawRef);
        return !empty($cleaned) ? strtoupper($cleaned) : null;
    }

    public static function normalizeProvider(?string $rawProvider): string
    {
        $valid = ['GCash', 'Maya', 'BDO', 'BPI', 'Metrobank', 'UnionBank', 'LandBank'];
        foreach ($valid as $v) {
            if (strcasecmp($v, trim($rawProvider ?? '')) === 0) {
                return $v;
            }
        }

        return 'Other';
    }
}
