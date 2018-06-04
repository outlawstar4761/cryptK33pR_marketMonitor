<?php namespace Bittrex;

/*BITTREX REQUEST produces:
 * * $output->success int
 * * $output->message string
 * * $output->result (array of objects)
 */
//Market history applies only to recent trades | very short time period

//$b = new Bittrex();
//b->getTicker("BTC-LTC")

class BitFactory{
    
    public static function exception($exceptionStr){
        return new \Exception($exceptionStr);
    }
}

class Bittrex{

    const APIBASE_PUBLIC = 'https://bittrex.com/api/v1.1/public/';
    
    public $orderBook;
    public $currencies;
    public $ticker;
    public $marketSummary;
    public $marketHistory;
    public $markets = array();
    
    public function __construct(){
        $this->getMarkets();
    }
    protected function _publicApiCall($method){
        $url = self::APIBASE_PUBLIC . $method;
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        $output = json_decode(curl_exec($ch));
        curl_close($ch);
        if(!$output->success){
            throw BitFactory::exception($output->message);
        }
        return $output->result;
    }
    public function getMarkets(){
        $this->markets = $this->_publicApiCall('getmarkets');
        return $this;
    }
    public function getCurrencies(){
        $this->currencies = $this->_publicApiCall('getcurrencies');
        return $this;
    }
    public function getTicker($market){
        $this->ticker = $this->_publicApiCall('getticker?market=' . $market);
        return $this;
    }
    public function getMarketSummaries(){
        $this->marketSummary = $this->_publicApiCall('getmarketsummaries');
        return $this;
    }
    public function getMarketSummary($market){
        $this->marketSummary = $this->_publicApiCall('getmarketsummary?market=' . $market);
        return $this;
    }
    public function getOrderBook($market,$type,$depth){
        //type = 'buy' or 'sell'
        $this->orderBook = $this->_publicApiCall('getorderbook?market=' . $market . "&type=" . $type . "&depth=" . $depth);
        return $this;
    }
    public function getMarketHistory($market){
        $this->marketHistory = $this->_publicApiCall("getmarkethistory?market=" . $market);
        return $this;
    }
    
}
