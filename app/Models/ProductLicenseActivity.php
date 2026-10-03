<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ProductLicenseActivity extends Model
{
    public const USER_AGENT_MAX_LENGTH = 500;

    protected $fillable = [
        'product_license_id',
        'event',
        'attempted_domain',
        'license_key_fingerprint',
        'ip_address',
        'user_agent',
        'http_status',
    ];

    protected function userAgent(): Attribute
    {
        return Attribute::make(
            set: static fn (?string $value): ?string => $value === null
                ? null
                : Str::substr(
                    $value,
                    0,
                    self::USER_AGENT_MAX_LENGTH
                ),
        );
    }

    public function license(): BelongsTo
    {
        return $this->belongsTo(
            ProductLicense::class,
            'product_license_id'
        );
    }
}
