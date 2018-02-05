<?php

namespace coinmonkey\exchangers\tools\Vendor;

class Poloniex {
    protected $api_key;
    protected $api_secret;
    protected $trading_url = "https://poloniex.com/tradingApi";
    protected $public_url = "https://poloniex.com/public";

    public function __construct($api_key, $api_secret) {
        $this->api_key = $api_key;
        $this->api_secret = $api_secret;
    }

    private function query(array $req = array()) {
        // API settings
        $key = $this->api_key;
        $secret = $this->api_secret;

        // generate a nonce to avoid problems with 32bit systems
        $mt = explode(' ', microtime());
        $req['nonce'] = $mt[1].substr($mt[0], 2, 6);

        // generate the POST data string
        $post_data = http_build_query($req, '', '&');
        $sign = hash_hmac('sha512', $post_data, $secret);

        // generate the extra headers
        $headers = array(
            'Key: '.$key,
            'Sign: '.$sign,
        );

        // curl handle (initialize if required)
        static $ch = null;
        if (is_null($ch)) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT,
                'Mozilla/4.0 (compatible; Poloniex PHP bot; '.php_uname('a').'; PHP/'.phpversion().')'
            );
        }
        curl_setopt($ch, CURLOPT_URL, $this->trading_url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3000);

        // run the query
        $res = curl_exec($ch);

        if ($res === false) throw new \Exception('Curl error: '.curl_error($ch));
        //echo $res;
        $dec = json_decode($res, true);
        if (!$dec){
            //throw new Exception('Invalid data: '.$res);
            return false;
        }else{
            return $dec;
        }
    }

    protected function retrieveJSON($URL) {
        $opts = array('http' =>
            array(
                'method'  => 'GET',
                'timeout' => 10
            )
        );
        $context = stream_context_create($opts);
        $feed = file_get_contents($URL, false, $context);
        $json = json_decode($feed, true);
        return $json;
    }

    public function get_currencies() {
        return $this->retrieveJSON($this->public_url . '?command=returnCurrencies');
    }

    public function get_balances() {
        return $this->query(
            array(
                'command' => 'returnBalances'
            )
        );
    }

    public function get_open_orders($pair) {
        return $this->query(
            array(
                'command' => 'returnOpenOrders',
                'CoinPair' => strtoupper($pair)
            )
        );
    }

    public function get_my_trade_history($pair) {
        return $this->query(
            array(
                'command' => 'returnTradeHistory',
                'CoinPair' => strtoupper($pair)
            )
        );
    }

    public function buy($pair, $rate, $amount) {
        return $this->query(
            array(
                'command' => 'buy',
                'CoinPair' => strtoupper($pair),
                'rate' => $rate,
                'amount' => $amount
            )
        );
    }

    public function sell($pair, $rate, $amount) {
        return $this->query(
            array(
                'command' => 'sell',
                'CoinPair' => strtoupper($pair),
                'rate' => $rate,
                'amount' => $amount
            )
        );
    }

    public function cancel_order($pair, $order_number) {
        return $this->query(
            array(
                'command' => 'cancelOrder',
                'CoinPair' => strtoupper($pair),
                'orderNumber' => $order_number
            )
        );
    }

    public function withdraw($coin, $amount, $address) {
        return $this->query(
            array(
                'command' => 'withdraw',
                'Coin' => strtoupper($coin),
                'amount' => $amount,
                'address' => $address
            )
        );
    }

    public function get_trade_history($pair) {
        $trades = $this->retrieveJSON($this->public_url.'?command=returnTradeHistory&CoinPair='.strtoupper($pair));
        return $trades;
    }

    public function get_order_book($pair) {
        $orders = $this->retrieveJSON($this->public_url.'?command=returnOrderBook&CoinPair='.strtoupper($pair));
        return $orders;
    }

    //returnDepositAddresses
    public function get_deposit_address(string $coin)
    {
        $return = $this->query(
            array(
                'command' => 'returnDepositAddresses'
            )
        );

        return $return[$coin];
    }

    public function get_deposit_withdraw_history($time)
    {
        $return = $this->query(
            array(
                'command' => 'returnDepositsWithdrawals',
                'start' => time()-$time,
                'end' => time(),
            )
        );

        return $return;
    }

    public function get_volume() {
        $volume = $this->retrieveJSON($this->public_url.'?command=return24hVolume');
        return $volume;
    }

    public function get_ticker($pair = "ALL") {
        $pair = strtoupper($pair);
        $prices = $this->retrieveJSON($this->public_url.'?command=returnTicker');
        if($pair == "ALL"){
            return $prices;
        }else{
            $pair = strtoupper($pair);
            if(isset($prices[$pair])){
                return $prices[$pair];
            }else{
                return array();
            }
        }
    }

    public function get_trading_pairs() {
        $tickers = $this->retrieveJSON($this->public_url.'?command=returnTicker');
        return array_keys($tickers);
    }

    public function get_total_btc_balance() {
        $balances = $this->get_balances();
        $prices = $this->get_ticker();

        $tot_btc = 0;

        foreach($balances as $coin => $amount){
            $pair = "BTC_".strtoupper($coin);

            // convert coin balances to btc value
            if($amount > 0){
                if($coin != "BTC"){
                    $tot_btc += $amount * $prices[$pair];
                }else{
                    $tot_btc += $amount;
                }
            }

            // process open orders as well
            if($coin != "BTC"){
                $open_orders = $this->get_open_orders($pair);
                foreach($open_orders as $order){
                    if($order['type'] == 'buy'){
                        $tot_btc += $order['total'];
                    }elseif($order['type'] == 'sell'){
                        $tot_btc += $order['amount'] * $prices[$pair];
                    }
                }
            }
        }

        return $tot_btc;
    }
}