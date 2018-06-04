<?php namespace Cryptopia;

/*Cryptopia requests return:
 * $output->success | bool
 * $output->Message | str
 * $output->data | array of objects
 * 
 *  */

//tradeid = tradepairId or marketName

//$c = new Cryptopia();
//try{
//    print_r($c->getMarket('NYAN_DOGE'));
//}catch(\Exception $e){
//    echo $e->getMessage() . "\n";
//}

class CryptFactory{
    public static function exception($exceptionStr){
        return new \Exception($exceptionStr);
    }
}

class Cryptopia{
    
    const YEARHRS = 8760;

    const APIBASE = 'https://www.cryptopia.co.nz/api/';
    
    public $market;
    public $marketHistory;
    public $currencies;
    public $marketOrders;
    
    public function __construct(){
        $this->getTradePairs();
    }
    protected function _apiCall($method){
        $url = self::APIBASE . $method;
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        $output = json_decode(curl_exec($ch));
        curl_close($ch);
        if(!is_null($output->Error)){
            throw CryptFactory::exception($output->Error);
        }
        return $output->Data;
    }
    public function getCurrencies(){
        $this->currencies = $this->_apiCall('GetCurrencies');
        return $this;
    }
    public function getTradePairs(){
        $this->tradePairs = $this->_apiCall('GetTradePairs');
        return $this;
    }
    public function getMarkets($baseMarket = null,$hours = null){
        $method = 'GetMarkets';
        if(!is_null($baseMarket) && !is_null($hours)){
            $method .= '/' . $baseMarket . '/' . $hours;
        }elseif(!is_null($baseMarket) && is_null($hours)){
            $method = '/' . $baseMarket;
        }elseif(is_null($baseMarket) && !is_null($hours)){
            $method = '/' . $hours;
        }
        $this->markets = $this->_apiCall($method);
        return $this;
    }
    public function getMarket($tradeId = null,$hours = null){
        $method = 'GetMarket';
        if(!is_null($tradeId) && !is_null($hours)){
            $method .= '/' . $tradeId . '/' . $hours;
        }elseif(!is_null($tradeId) && is_null($hours)){
            $method .= '/' . $tradeId;
        }elseif(is_null($tradeId) && !is_null($hours)){
            $method .= '/' . $hours;
        }
        $this->market = $this->_apiCall($method);
        return $this;
    }
    public function getMarketHistory($tradeId,$hours = null){
        $method = 'GetMarketHistory';
        if(!is_null($tradeId) && !is_null($hours)){
            $method .= '/' . $tradeId . '/' . $hours;
        }elseif(!is_null($tradeId) && is_null($hours)){
            $method .= '/' . $tradeId;
        }elseif(is_null($tradeId) && !is_null($hours)){
            $method .= '/' . $hours;
        }
        $this->marketHistory = $this->_apiCall($method);
        return $this;
    }
    public function getMakretOrders($tradeId,$orderCount = null){
        $method = 'GetMarketOrders';
        if(!is_null($orderCount)){
            $method .= '/' . $tradeId . '/' . $orderCount;
        }else{
            $method .= '/' . $tradeId;
        }
        $this->marketOrders = $this->_apiCall($method);
        return $this;
    }
    public function getMarketOrderGroups($orderGroups,$orderCount = null){
        foreach($orderGroups as $orderGroup){
            $method = 'GetMarketOrders';
            if(is_null($orderCount)){
                $method .= '/' . $orderGroup;
            }else{
                $method .= '/' . $orderGroup . '/' . $orderCount;
            }
            $this->marketOrders[$orderGroup] = $this->_apiCall($method);
        }
        return $this;
    }
    
}
