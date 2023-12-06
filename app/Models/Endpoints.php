<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Endpoints extends Model
{
    use HasFactory;

    public function getConversionEndpointUrlAttribute() {
        return str_replace('{access_key}',trim($this->provider()->first()->api_key),$this->attributes['conversion_endpoint_url']);
    }

    public function getHistoricalEndpointUrlAttribute() {
        return str_replace('{access_key}',trim($this->provider()->first()->api_key),$this->attributes['historical_endpoint_url']);
    }

    public function getCurrencySymbolsEndpointUrlAttribute() {
        return str_replace('{access_key}',trim($this->provider()->first()->api_key),$this->attributes['currency_symbols_endpoint_url']);
    }
    public function provider(): BelongsTo
    {
        return $this->belongsTo(Providers::class);
    }
}
