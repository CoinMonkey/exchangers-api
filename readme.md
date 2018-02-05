# PHP Libs for popular crypto-exchangers

The following methods are available for Poloniex, Bittrex (classic exchangers), Shapeshift, Changer, Changelly, Nexchange (instant exchangers).

## Install

```
composer require coinmonkey/exchangers-api @dev
```

Please note that libraries are in in development. Don't use it in procution projects.

Production version coming soon (but I'm not sure).

## Example

```php
<?php
use \coinmonkey\exchangers\ExchangerFabric;
use \coinmonkey\entities\Coin;
use \coinmonkey\entities\Amount;

$instantExchangers = ['shapeshift', 'changer', 'changelly', 'bittrex', 'poloniex']; //Fabric support these instant exchangers
$exchangers = ['bittrex', 'poloniex']; //Fabric support these exchangers

//Creating fabric with your config
$config = [
    'CHANGER_API_NAME' => env('CHANGER_API_NAME'),
    'CHANGER_API_KEY' => env('CHANGER_API_KEY'),
    'CHANGER_API_SECURE' => env('CHANGER_API_SECURE'),
    'CHANGER_API_NAME' => env('CHANGER_API_NAME'),
    'CHANGER_API_KEY' => env('CHANGER_API_KEY'),
    'CHANGER_API_SECURE' => env('CHANGER_API_SECURE'),
    'CHANGELLY_API_KEY' => env('CHANGELLY_API_KEY'),
    'CHANGELLY_API_SECRET' => env('CHANGELLY_API_SECRET'),
    'SHAPESHIFT_API_KEY' => env('SHAPESHIFT_API_KEY'),
    'SHAPESHIFT_API_SECRET' => env('SHAPESHIFT_API_SECRET'),
    'POLONIEX_API_KEY' => env('POLONIEX_API_KEY'),
    'POLONIEX_API_SECRET' => env('POLONIEX_API_SECRET'),
    'BITTREX_API_KEY' => env('BITTREX_API_KEY'),
    'BITTREX_API_SECRET' => env('BITTREX_API_SECRET'),
];
$fabric = new ExchangerFabric($config);

$coin1 = new Coin('ETH', 'Ether'); //This coin we want to exchange...
$coin2 = new Coin('BTC', 'Bitcoin'); //To this coin
$amount = new Amount(1.3, $coin1); //Creating 1.3 amount of $coin1 for exchanging

//Working with instant exchangers (classic exchangers can work in this mode to)
foreach($instantExchangers as $exchangerName) {
    //Creating exchanger instance by name
    $exchanger = $fabric->buildInstant($exchangerName);
    $estimateAmount = $exchanger->getEstimateAmount($amount, $coin2)->getAmount();
    echo "* $exchangerName estimate is " . $estimateAmount . "\n";
    //If it's OK you can call $order = $exchanger->makeDepositAddress('1JBPkwCuTpwcyEbhBkAKdTfU9K1zRLX5Ym', $amount, $coin2)
    //And check status later by  $exchanger->getStatus($order['id']);
}

//Working with classic exchangers
foreach($exchangers as $exchangerName) {
    $exchanger = $fabric->build($exchangerName);
    $estimateAmount = $exchanger->getEstimateAmount($amount, $coin2)->getAmount();
    echo "* $exchangerName estimate is " . $estimateAmount . "\n";
    echo " - withdrawal fee " .  $exchanger->getWithdrawalFee($coin2) . "\n";
    echo " - min coinfirmations " .  $exchanger->getMinConfirmations($coin1) . "\n";
    //If it's OK you can call $order = $exchanger->makeDepositAddress('1JBPkwCuTpwcyEbhBkAKdTfU9K1zRLX5Ym', $amount, $coin2)
    //And check status later by  $exchanger->getStatus($order['id']);
}
```
