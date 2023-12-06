<?php

namespace Database\Seeders;

use App\Models\Providers;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EndpointsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $providers = Providers::whereStatus(1)->get();
        foreach($providers  as $provider) {
            if($provider->name == 'ExchangeRate-API') {
                DB::table('endpoints')->insert([
                    'provider_id'                   => $provider->id,
                    'conversion_endpoint_url'       => 'https://v6.exchangerate-api.com/v6/{access_key}/pair/{from_currency}/{to_currency}',
                    'currency_symbols_endpoint_url' => 'https://v6.exchangerate-api.com/v6/{access_key}/codes',
                    'historical_endpoint_url'       => null,
                    'created_at'                    => now()
                ]);
            } elseif ($provider->name == 'currencylayer') {
                DB::table('endpoints')->insert([
                    'provider_id'                   => $provider->id,
                    'conversion_endpoint_url'       => 'http://api.currencylayer.com/convert?from={from_currency}&to={to_currency}&amount={amount}&access_key={access_key}',
                    'currency_symbols_endpoint_url' => 'http://api.currencylayer.com/list?access_key={access_key}',
                    'historical_endpoint_url'       => 'http://api.currencylayer.com/historical?date={date}&access_key={access_key}',
                    'created_at'                    => now()
                ]);
            } else {
                DB::table('endpoints')->insert([
                    'provider_id'                   => $provider->id,
                    'currency_symbols_endpoint_url' => 'http://api.exchangerate.host/list?access_key={access_key}',
                    'conversion_endpoint_url'       => 'http://api.exchangerate.host/convert?from={from_currency}&to={to_currency}&amount={amount}&access_key={access_key}',
                    'historical_endpoint_url'       => 'http://api.exchangerate.host/convert?access_key={access_key}&from={from_currency}&to={to_currency}&amount={amount}&date={date}',
                    'created_at'                    => now()
                ]);
            }
        }
    }
}
