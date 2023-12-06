<?php

namespace App\Services;
use App\Interfaces\APIServiceInterface;
use Illuminate\Database\Eloquent\Collection;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;


class ExchangeratehostConversionService extends ConversionService implements APIServiceInterface
{
    private string $currencyFieldName = 'currencies';

    /**
     * Get currencies symbols from cache or make an api call if cache key is not set
     *
     * @param string $currentProviderName
     * @return array
     */
    public function getCurrenciesSymbols(string $currentProviderName, string $currencyField): array
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

    // the error response comes back with the wrong status (200 from Currencylayer so this one requires a custom function)
    public function getResponseFromEndpoint(string $endpointUrl): array
    {
        $client = new Client();
        try {
            $response = $client->request('GET', trim($endpointUrl));

            if ($response->getStatusCode() == 200) {

                // check for error here
                $body = json_decode($response->getBody(), true);

                return ($body['success'] === false)?['error' => $body['error']['info'], 'statusCode'=>$body['error']['code']]:
                    ['data'=>$body,'success'=>true];
            } else {
                // Handle non-200 status codes
                return ['error' => 'Invalid response', 'statusCode'=>$response->getStatusCode()];
            }
        } catch (GuzzleException $e) {
            Log::error($e->getMessage());
            return ['error' => $e->getMessage(), 'statusCode'=>$e->getCode()];
        }
    }

    public function getCurrencyFieldName(): string
    {
        return $this->currencyFieldName;
    }

}
