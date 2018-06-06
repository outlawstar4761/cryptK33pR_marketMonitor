<?php namespace Crypt\Cron;

require_once __DIR__ . '/cryptopia.php';
require_once __DIR__ . '/bittrex.php';

while(true){
    try{
        $m = new Main();
    }catch(\Exception $e){
        echo $e->getMessage() . "\n";
    }
    sleep(900);
}

class Main{

    const CRYPTAPI = 'http://outlawdesigns.ddns.net:9662/';
    const USERNAME = 'outlaw';
    const PASSWORD = 'admin';

    protected $accessToken;

    public $bittrex;
    public $cryptopia;
    public $coins = array();
    public $bittrexPairs = array();
    public $cryptopiaPairs = array();
    public $marketSummaries = array();
    
    public function __construct(){
        $this->bittrex = new \Bittrex\Bittrex();
        $this->cryptopia = new \Cryptopia\Cryptopia();
        $this->_authenticate()
		->getCoins()
                ->buildBittrexMarkets()
                ->buildCryptopiaMarkets()
                ->getBittrexSummaries()
                ->getCryptopiaSummaries()
                ->postUpdates();
    }
    protected function _authenticate(){
        $headers = array('request_token: ' . self::USERNAME, 'password: ' . self::PASSWORD);
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,self::CRYPTAPI . 'authenticate');
        curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        $output = json_decode(curl_exec($ch));
        curl_close($ch);
        if(isset($output->error)){
            throw new \Exception($output->error);
        }
        $this->accessToken = $output->token;
        return $this;
    }
    protected function _apiGet($endpoint){
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,self::CRYPTAPI . $endpoint);
        curl_setopt($ch,CURLOPT_HTTPHEADER,array('auth_token: ' . $this->accessToken));
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        $output = json_decode(curl_exec($ch));
        curl_close($ch);
        if(isset($output->error)){
            throw new \Exception($output->error);
        }
        return $output;
    }
    protected function _apiPost($endpoint,$data){
        $headers = array(
            'auth_token: ' . $this->accessToken,
            'Content-Type: application/json',
            'Content-Length: ' . strlen(json_encode($data))
            );
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,self::CRYPTAPI . $endpoint);
        curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $output = curl_exec($ch);
        if(isset($output->error)){
            throw new \Exception($output->error);
        }
        return $output;
    }
    protected function _verifyToken(){
        try{
            $this->_apiGet('verify');
        }catch(\Exception $e){
            return false;
        }
        return true;
    }
    public function getCoins(){
        $coins = $this->_apiGet('coin');
        foreach($coins as $coin){
            $this->coins[] = $coin->abbreviation;
        }
        return $this;
    }
    public function buildBittrexMarkets(){
        foreach($this->bittrex->markets as $market){
            if(in_array($market->MarketCurrency,$this->coins)){
                $this->bittrexPairs[] = $market->BaseCurrency . '-' . $market->MarketCurrency;
            }
        }
        return $this;
    }
    public function buildCryptopiaMarkets(){
        foreach($this->cryptopia->tradePairs as $market){
            if(in_array($market->Symbol,$this->coins)){
                $this->cryptopiaPairs[] = $market->Symbol . '_' . $market->BaseSymbol;
            }
        }
        return $this;
    }
    public function getBittrexSummaries(){
        foreach($this->bittrexPairs as $market){
            $this->bittrex->getMarketSummary($market);
            $summary = array(
                "market"=>$market,
                "bid"=>$this->bittrex->marketSummary[0]->Bid,
                "ask"=>$this->bittrex->marketSummary[0]->Ask,
                "high"=>$this->bittrex->marketSummary[0]->High,
                "low"=>$this->bittrex->marketSummary[0]->Low,
                "volume"=>$this->bittrex->marketSummary[0]->Volume,
                "baseVolume"=>$this->bittrex->marketSummary[0]->BaseVolume,
                "openSellOrders"=>$this->bittrex->marketSummary[0]->OpenSellOrders,
                "openBuyOrders"=>$this->bittrex->marketSummary[0]->OpenBuyOrders,
                "created_date"=>date("Y-m-d H:i:s"),
                "source"=>"bittrex"
            );
            $this->marketSummaries[] = $summary;
        }
        return $this;
    }
    public function getCryptopiaSummaries(){
        foreach($this->cryptopiaPairs as $market){
            $this->cryptopia->getMarket($market);
            $summary = array(
                "market"=>$this->flipMarketBase($market),
                "bid"=>$this->cryptopia->market->BidPrice,
                "ask"=>$this->cryptopia->market->AskPrice,
                "high"=>$this->cryptopia->market->High,
                "low"=>$this->cryptopia->market->Low,
                "volume"=>$this->cryptopia->market->Volume,
                "baseVolume"=>$this->cryptopia->market->BaseVolume,
                "created_date"=>date("Y-m-d H:i:s"),
                "source"=>"cryptopia"
            );
            $this->marketSummaries[] = $summary;
        }
        $this->getCryptopiaOrderCounts();
        return $this;
    }
    private function getCryptopiaOrderCounts(){
        foreach($this->cryptopiaPairs as $market){
            $this->cryptopia->getMakretOrders($market);
            for($i = 0; $i < count($this->marketSummaries); $i++){
                if($this->marketSummaries[$i]['market'] == $this->flipMarketBase($market) && $this->marketSummaries[$i]['source'] == 'cryptopia'){
                    $this->marketSummaries[$i]['openSellOrders'] = count($this->cryptopia->marketOrders->Sell);
                    $this->marketSummaries[$i]['openBuyOrders'] = count($this->cryptopia->marketOrders->Buy);
                }
            }
        }
        return $this;
    }
    private function flipMarketBase($market){
        $pieces = explode('_',$market);
        return $pieces[1] . '-' . $pieces[0];
    }
    private function postUpdates(){
        $endpoint = 'market_history';
        foreach($this->marketSummaries as $summary){
            $this->_apiPost($endpoint,$summary);
        }
        return $this;
    }
}
