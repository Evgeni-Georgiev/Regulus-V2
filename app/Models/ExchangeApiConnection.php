<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExchangeApiConnection extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'exchange_name',
        'api_key',
        'api_secret',
        'additional_params',
        'last_synced_at',
        'is_active'
    ];

    protected $casts = [
        'additional_params' => 'array',
        'last_synced_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'api_key',
        'api_secret',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
