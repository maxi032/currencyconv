# Currency Converter in Laravel using multiple providers

This is a simple currency converter app using multiple currency API providers to get Exchange quotes. 

You will need a free account on 
- https://www.exchangerate-api.com/
- https://currencylayer.com/
- https://exchangerate.host/

You can get access to 1000 requests / month.

Install with composer:

``` composer install ```

``` cp .env.example .env ```

``` php artisan key:generate ```


Add a database and set the credentials in .env file.
Run the migrations and seeders

```php artisan migrate --seed```

```npm install && npm run build```

You can login with ```bogdan@test.com``` and ```password```

Note: The provider ExchangeRate-API does not offer historical data within an interval so the chart is not showing when that provider is used.

For the frontend I used Bootstrap5 and jQuery and for the chart I used https://www.highcharts.com/  

![Converter form](https://raw.github.com/maxi032/currencyconv/master/public/assets/img/s1.png) 



