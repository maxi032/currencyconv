<?php

namespace App\Enums;

enum ProvidersEnum: string
{
    case EXCHANGERATEAPI = 'ExchangeRate-API';
    case CURRENCYLAYER = 'Currencylayer';
    case EXCHANGERATEHOST = 'Exchangerate-host';
}
