<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProvidersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('providers')->updateOrInsert([
            'monthly_requests' => 1500,
            'name' => 'ExchangeRate-API',
            'api_key' => 'de46e14c693c4ab83e446511',
            'calculates_total' => 0,
            'status' => 1,
            'created_at'=>now()
        ]);

        DB::table('providers')->updateOrInsert([
            'monthly_requests' => 1000,
            'name' => 'Currencylayer',
            'api_key' => '4d9cd1caf6288827caac7347db28b992',
            'calculates_total' => 1,
            'status' => 1,
            'created_at'=>now()
        ]);

        DB::table('providers')->updateOrInsert([
            'monthly_requests' => 1000,
            'name' => 'Exchangerate-host',
            'api_key' => '2228a2d2ba7c68b2b35a2901225950f8',
            'calculates_total' => 1,
            'status' => 1,
            'created_at'=>now()
        ]);
    }
}
