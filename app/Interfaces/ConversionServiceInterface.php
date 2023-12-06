<?php

namespace App\Interfaces;

interface ConversionServiceInterface
{
    public function getCurrenciesSymbols(string $currentProviderName, string $currencyField);
 }
