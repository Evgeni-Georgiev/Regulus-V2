<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Coin extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'symbol',
        'price',
        'market_cap',
        'percent_change_1h',
        'percent_change_24h',
        'percent_change_7d',
        'volume_24h',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'price' => 'float',
        'market_cap' => 'float',
        'percent_change_1h' => 'float',
        'percent_change_24h' => 'float',
        'percent_change_7d' => 'float',
        'volume_24h' => 'float',
    ];

    /**
     * Define the many-to-many relationship with portfolios.
     */
    public function portfolios(): BelongsToMany
    {
        return $this->belongsToMany(Portfolio::class, 'portfolio_coins')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function getMarketCapFormatAttribute(): string
    {
        return number_format($this->market_cap);
    }

    public function getVolumeFormatAttribute(): string
    {
        return number_format($this->volume_24h);
    }

    public function getPriceFormatAttribute(): string
    {
        return number_format($this->price, 2);
    }
}
