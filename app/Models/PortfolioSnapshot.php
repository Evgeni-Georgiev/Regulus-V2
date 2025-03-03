<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortfolioSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'portfolio_id',
        'total_portfolio_value',
        'recorded_at',
    ];

    public $timestamps = false;

    protected $dates = ['recorded_at'];

    protected $casts = [
        'total_portfolio_value' => 'float',
        'recorded_at' => 'datetime',
    ];

    /**
     * A Snapshot belongs to single Portfolio.
     *
     * @return BelongsTo
     */
    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }
}
