<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CoinResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'symbol' => $this->symbol,
            'price' => $this->price,
            'market_cap' => $this->market_cap,
            'percent_change_1h' => $this->percent_change_1h,
            'percent_change_24h' => $this->percent_change_24h,
            'percent_change_7d' => $this->percent_change_7d,
            'volume_24h' => $this->volume_24h,
        ];
    }
}
