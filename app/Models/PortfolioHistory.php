<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortfolioHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'portfolio_id',
        'previous_value',
        'new_value',
        'change_type',
        'change_value',
        'changed_at'
    ];

    protected $casts = [
        'previous_value' => 'float',
        'new_value' => 'float',
        'change_amount' => 'float',
        'changed_at' => 'datetime',
    ];

    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }

}
