<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'price' => 'decimal:2',
        'market_cap' => 'decimal:2',
        'percent_change_1h' => 'decimal:2',
        'percent_change_24h' => 'decimal:2',
        'percent_change_7d' => 'decimal:2',
        'volume_24h' => 'decimal:2',
    ];

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
