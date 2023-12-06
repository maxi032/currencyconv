<?php

namespace App\Services;
use App\Interfaces\APIServiceInterface;
use Illuminate\Database\Eloquent\Collection;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;


class ExchangeRateAPIConversionService extends ConversionService implements APIServiceInterface
{
    private string $currencyFieldName = 'supported_codes';

    /**
     * Get currencies symbols from cache or make an api call if cache key is not set
     *
     * @param string $currentProviderName
     * @return array
     */
    public function getCurrenciesSymbols(string $currentProviderName, $currencyField): array
    {
        $cacheKey = 'currencies_symbols_' . $currentProviderName;
        $cacheDuration = config('currencyconversion.cacheTTL'); // Cache duration in seconds

        // Try to get data from cache
        $cachedData = Cache::get($cacheKey);

        if ($cachedData) {
            // Cache hit, return cached data
            return $cachedData;
        }

        // Cache miss, make API request
        $currentProvider = $this->getDatabaseProvider($currentProviderName);
        $currenciesEndpoint = $currentProvider->endpoints->currency_symbols_endpoint_url;
        $result = $this->getResponseFromEndpoint($currenciesEndpoint);

        // Prepare the data to be returned and cached
        $dataToReturn = is_array($result) && isset($result['success'])
            ? ['success' => true, 'data' => $result['data'][$currencyField]]
            : ['error' => $result['error'], 'statusCode' => $result['statusCode']];

        // Cache the data
        Cache::put($cacheKey, $dataToReturn, $cacheDuration);

        return $dataToReturn;
    }

    public function getCurrencyFieldName(): string
    {
        return $this->currencyFieldName;
    }

}
