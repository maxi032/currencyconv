<?php

namespace App\Http\Controllers;

use App\Services\ConversionService;
use \App\Enums\ProvidersEnum;
use App\Services\ExchangeRateAPIConversionService;
use App\Services\CurrencylayerConversionService;
use App\Services\ExchangeratehostConversionService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use ReflectionException;

class HomeController extends Controller
{

    private array $serviceInstances = [];

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Foundation\Application
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function index()
    {
        $this->setProviderSessionVar();
        $currentProvider = session()->get('provider');
        $providerService = $this->instantiateServiceProvider($currentProvider);

        // get all active providers from db
        $providers = $providerService->getProviders();

        $currencyFieldName = $providerService->getCurrencyFieldName();

        // Get currency symbols of the current provider
        $currencies = $providerService->getCurrenciesSymbols($currentProvider, $currencyFieldName);

        return view('home', [
            'providers'  => $providers,
            'currencies' => (isset($currencies['success'])) ? $providerService->prepareCurrencies($currencies['data'], $currentProvider) : $currencies,
        ]);
    }


    public function setProviderSessionVar($param = null): void
    {
        // Check if the session variable is already set
        if (!Session::has('provider')) {
            $value = $param ?? Config::get('currencyconversion.defaultProvider');

            // Set the session variable
            Session::put('provider', $value);
        } elseif (request()->ajax()) {
            session::put('provider', request()->get('value'));
        }
    }


    /**
     * Make a new conversion with post variables $from $to $amount
     *
     * @param Request $request
     * @return mixed
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function newConversion(Request $request): mixed
    {
        $from = $request->input('from');
        $to = $request->input('to');
        $amount = $request->input('amount');
        $providerName = session()->get('provider');

        switch ($providerName) {
            case ProvidersEnum::CURRENCYLAYER->value:
                $conversionService = new CurrencylayerConversionService();

                $provider = $conversionService->getProviderByName($providerName);
                $endpointsArr = [];
                $endpoints = $provider->endpoints;
                $endpointsArr['conversion'] = str_replace(['{from_currency}', '{to_currency}', '{amount}'], [$from, $to, $amount], $endpoints->conversion_endpoint_url);
                $conversionResult = $conversionService->getResponseFromEndpoint($endpointsArr['conversion']);
                if ($provider->calculates_total && isset($conversionResult['data']['success']) && $conversionResult['data']['success'] === true) {
                    $result = $conversionResult['data']['result'];
                    $conversionDate = date('d/m/Y H:i:s', $conversionResult['data']['info']['timestamp']);
                    $parity = 1 * $result / $amount;
                    $parity2 = 1 * $amount / $result;
                } else {
                    $result = ['error' => $conversionResult['error'], 'statusCode' => $conversionResult['statusCode'], 'parity' => 0];
                }
                break;

            case ProvidersEnum::EXCHANGERATEHOST->value:
                $conversionService = new ExchangeratehostConversionService();
                // Get provider from database
                $provider = $conversionService->getProviderByName($providerName);
                $endpointsArr = [];
                $endpoints = $provider->endpoints;
                $endpointsArr['conversion'] = str_replace(['{from_currency}', '{to_currency}', '{amount}'], [$from, $to, $amount], $endpoints->conversion_endpoint_url);
                $conversionResult = $conversionService->getResponseFromEndpoint($endpointsArr['conversion']);

                if ($provider->calculates_total && isset($conversionResult['data']['success']) && $conversionResult['data']['success'] === true) {
                    $result = $conversionResult['data']['result'];
                    $conversionDate = date('d/m/Y H:i', $conversionResult['data']['info']['timestamp']);
                    $parity = 1 * $result / $amount;
                    $parity2 = 1 * $amount / $result;
                } else {
                    $result = ['error' => $conversionResult['error'], 'statusCode' => $conversionResult['statusCode'], 'parity' => 0];
                }
                break;
            default:
                $conversionService = new ExchangeRateAPIConversionService();
                $provider = $conversionService->getProviderByName($providerName);
                $endpointsArr = [];
                $endpoints = $provider->endpoints;
                $endpointsArr['conversion'] = str_replace(['{from_currency}', '{to_currency}'], [$from, $to], $endpoints->conversion_endpoint_url);
                $conversionResult = $conversionService->getResponseFromEndpoint($endpointsArr['conversion']);
                if (!$provider->calculates_total && isset($conversionResult['data']['conversion_rate'])) {
                    $result = $amount * $conversionResult['data']['conversion_rate'];
                    $parity = $conversionResult['data']['conversion_rate'];
                    $parity2 = 1 * $amount / $result;
                    $conversionDate = date('d/m/Y H:i', $conversionResult['data']['time_last_update_unix']);
                } else {
                    $result = ['error' => $conversionResult['error'], 'statusCode' => $conversionResult['statusCode'], 'parity' => null];
                }
                break;
        }

        return (isset($conversionResult['success'])) ?
            ['result' => $result, 'parity' => $parity, 'parity2' => $parity2, 'conversionDate' => $conversionDate] : $result;
    }

    /**
     * When a provider is changed, refresh the currency dropdowns with ajax accordingly and keep the selected options
     *
     */
    public function refreshCurrenciesDropdowns(Request $request)
    {
        $from = $request->input('from');
        $to = $request->input('to');
        $currentProvider = session()->get('provider');

        // this will take the instance from array if it is already set.
        $providerService =  $this->instantiateServiceProvider($currentProvider);

        $currencyFieldName = $providerService->getCurrencyFieldName();

        // Get currency symbols of the current provider
        $currencies = $providerService->getCurrenciesSymbols($currentProvider, $currencyFieldName);
        $preparedCurrencies =  $providerService->prepareCurrencies($currencies['data'], $currentProvider);

        //check if $from and $to are in the new currencies
        $fromExists = $toExists = true;

        foreach($preparedCurrencies as $currencyk => $currency){
            if($from) { // do the verifycation only if $from is set
                if (!property_exists($currency, 'code') && strtoupper($currency->code) === strtoupper($from)) {
                    $fromExists = false;
                }
            }


            if($to) { // do the verifycation only if $to is set
                if (!property_exists($currency, 'code') && strtoupper($currency->code) === strtoupper($to)) {
                    $toExists = false;
                }
            }
        }


        return ($fromExists && $toExists &&  strlen($preparedCurrencies->first()->code) === 3) ? json_encode(['currencies'=>$preparedCurrencies, 'selected_from'=>$from, 'selected_to'=>$to]):json_encode(['error'=>'Code mismatch between the new currencies and the selected currencies']);
    }

    /**
     * Instantiate  service with the desired provider
     *
     * @param $providerName
     * @return ConversionService|mixed|void
     */
    public function instantiateServiceProvider($providerName)
    {
        $className = "App\\Services\\" . preg_replace('/[^a-zA-Z]/', '', ucfirst($providerName)) . 'ConversionService';

        // Check if the instance already exists and is of the correct type
        if (isset($this->serviceInstances[$className]) && $this->serviceInstances[$className] instanceof $className) {
            return $this->serviceInstances[$className];
        }

        try {
            $reflectionClass = new \ReflectionClass($className);
            /** @var ConversionService $conversionService */
            $conversionService = $reflectionClass->newInstance();

            // Store the instance for future use
            $this->serviceInstances[$className] = $conversionService;

            return $conversionService;

        } catch (ReflectionException $e) {
            Log::error("Class {$className} not found. " . $e->getMessage());
            abort(500, 'Internal Server Error');
        }
    }

}
