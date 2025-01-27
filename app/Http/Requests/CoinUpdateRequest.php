<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CoinUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'symbol' => ['required', 'string', 'max:20', 'unique:coins,symbol'],
            'price' => ['required', 'numeric', 'between:-999999999999999999.99,999999999999999999.99'],
            'market_cap' => ['required', 'numeric', 'between:-999999999999999999.99,999999999999999999.99'],
            'percent_change_1h' => ['required', 'numeric', 'between:-999999.99,999999.99'],
            'percent_change_24h' => ['required', 'numeric', 'between:-999999.99,999999.99'],
            'percent_change_7d' => ['required', 'numeric', 'between:-999999.99,999999.99'],
            'volume_24h' => ['required', 'numeric', 'between:-999999999999999999.99,999999999999999999.99'],
        ];
    }
}
