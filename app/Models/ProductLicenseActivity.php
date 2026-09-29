<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductLicenseActivity extends Model
{
    protected $fillable = [
        'product_license_id',
        'event',
        'attempted_domain',
        'license_key_fingerprint',
        'ip_address',
        'user_agent',
        'http_status',
    ];

    public function license(): BelongsTo
    {
        return $this->belongsTo(
            ProductLicense::class,
            'product_license_id'
        );
    }
}