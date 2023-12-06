<?php

namespace App\Services;

use App\Models\Providers;
use App\Interfaces\ConversionServiceInterface;
use App\Traits\ConversionTrait;
use App\Enums\ProvidersEnum;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ConversionService implements ConversionServiceInterface
{
    use ConversionTrait;

    /**
     * Get all active providers from database
     *
     * @return Collection
     */
    public function getProviders(): Collection
    {
        return Providers::with('endpoints')->whereStatus(1)->orderBy('name')->get();
    }

    public function getProviderByName(string $name)
    {
        return Providers::with('endpoints')->whereStatus(1)->whereName($name)->first();
    }

    /**
     * Make sure that the currencies comming from the selected provider are in the expected format by the dropdown
     *
     * @param $currencies
     * @param $selectedProvider
     * @return \Illuminate\Support\Collection
     */
    public function prepareCurrencies($currencies, $selectedProvider): \Illuminate\Support\Collection
    {
        $result = collect();
        switch ($selectedProvider) {
            case ProvidersEnum::EXCHANGERATEAPI->value:
                foreach ($currencies as $currencyk => $currency) {
                    $result->push((object)[
                        'code' => $currency[0],
                        'name' => $currency[1]
                    ]);
                }

                break;

            case ProvidersEnum::CURRENCYLAYER->value:
            case ProvidersEnum::EXCHANGERATEHOST->value:
                foreach ($currencies as $currencyk => $currency) {
                    $result->push((object)[
                        'code' => $currencyk,
                        'name' => $currency
                    ]);
                }

                break;

            default:
                foreach ($currencies as $currencyk => $currency) {
                    $result->push((object)[
                        'codee' => $currency[0],
                        'namee' => $currency[1]
                    ]);
                }
        }

        return $result;
    }

    /**
     * Make a get request to the endpoint.
     *
     * @param string $endpointUrl
     * @return array
     */
    public function getResponseFromEndpoint(string $endpointUrl): array
    {
        $client = new Client();
        try {
            $response = $client->request('GET', $endpointUrl);

            if ($response->getStatusCode() == 200) {
                $body = $response->getBody();
                return ['data'=>json_decode($body, true),'success'=>true];
            } else {
                // Handle non-200 status codes
                return ['error' => 'Invalid response', 'statusCode'=>$response->getStatusCode()];
            }
        } catch (GuzzleException $e) {
            Log::error($e->getMessage());
            return ['error' => $e->getMessage(), 'statusCode'=>$e->getCode()];
        }
    }

    /**
     * Get  selected provider from database
     *
     * @param string $currentProviderName
     * @return mixed
     */
    public function getDatabaseProvider(string $currentProviderName): mixed
    {
        return Providers::where('name',$currentProviderName)->first();
    }

    /**
     * Make request to get the currency symbols from the selected provider
     *
     * @param string $currentProviderName
     * @param string $currencyField
     * @return array|mixed
     */
    public function getCurrenciesSymbols(string $currentProviderName, string $currencyField)
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

}
