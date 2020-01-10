<?php
    class ExampleSMS_API {
        public $api_token = NULL;
        public $error     = NULL;
        public $rid       = 0;
        private $timeout  = 60;
        private $url      = "https://api.sms-provider.com/";

        public function __construct(){

        }

        public function set_credentials($api_token=''){
            $this->api_token   = $api_token;
        }

        private function curl_use ($site_url,$post_data=''){
            $ch = curl_init();
            curl_setopt($ch,CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
            curl_setopt($ch, CURLOPT_URL,$site_url);
            if($post_data){
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS,$post_data);
            }
            curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch,CURLOPT_USERPWD, $this->api_token.":");
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
            $result = curl_exec($ch);
            return $result;
        }

        public function Submit($title = NULL,$message = NULL,$number = 0){
            $numbers	= is_array($number) ? $number : [$number];

            $json_data  = [
                'sender'      => $title,
                'message'     => $message,
                'recipients'  => [],
            ];
            foreach ($numbers as $msisdn) $json_data['recipients'][] = ['msisdn' => $msisdn];

            $outcome = $this->curl_use($this->url.'rest/submit',Utility::jencode($json_data));
            $solve = Utility::jdecode($outcome,true);
            
            if($solve){

                if(isset($solve["code"]) && isset($solve["message"])){
                    $this->error = "Error: <".$solve["code"]."> ".$solve["message"];
                    return false;
                }

                if(!isset($solve["id"])){
                    $this->error = print_r($solve,true);
                    return false;
                }

                $rid 		    = $solve["id"];

                $this->rid    = $rid;

                return true;

            }else{
                $this->error = "The API response could not be resolved.";
                return false;
            }
        }

        public function Balance(){
            $outcome = $this->curl_use($this->url.'rest/me');
            $solve = Utility::jdecode($outcome,true);
            if($solve){
                if(isset($solve["credit"]) && isset($solve["currency"])){
                    return [
                        'balance'  => $solve["credit"],
                        'currency' => $solve["currency"],
                    ];
                }else{
                    $this->error = "Information is missing";
                    return false;
                }
            }else{
                $this->error = "The API response could not be resolved.";
                return false;
            }
        }

        public function ReportLook($rid){

            $outcome = $this->curl_use($this->url.'rest/report/'.$rid);
            $solve = Utility::jdecode($outcome,true);
            if($solve){

                if(isset($solve["code"]) && isset($solve["message"])){
                    $this->error = "Error: <".$solve["code"]."> ".$solve["message"];
                    return false;
                }

                if(isset($solve["recipients"])){

                    $result   = [
                        'enroute'     => [
                            'data' => [],
                            'count' => 0,
                        ],
                        'delivered'   => [
                            'data' => [],
                            'count' => 0,
                        ],
                        'undelivered' => [
                            'data' => [],
                            'count' => 0,
                        ],
                    ];

                    foreach($solve["recipients"] AS $recipient){
                        $number = $recipient["msisdn"];
                        if($recipient["dsnstatus"] == "ENROUTE"){
                            $result["enroute"]["data"][] = $number;
                            $result["enroute"]["count"]++;
                        }
                        elseif($recipient["dsnstatus"] == "DELIVERED"){
                            $result["delivered"]["data"][] = $number;
                            $result["delivered"]["count"]++;
                        }
                        elseif($recipient["dsnstatus"] == "UNDELIVERABLE"){
                            $result["undelivered"]["data"][] = $number;
                            $result["undelivered"]["count"]++;
                        }
                    }

                    return $result;
                }else{
                    $this->error = "not found recipients";
                    return false;
                }
            }else{
                $this->error = "The API response could not be resolved.";
                return false;
            }
        }

        public function get_prices(){
            $rows     = array();

            $outcome = $this->curl_use($this->url.'rest/pricing/');
            $solve = Utility::jdecode($outcome,true);
            if(isset($solve["data"]) && $solve["data"]){
                foreach($solve["data"] AS $row){
                    $rows[] = [
                        'countryCode' => $row["country"],
                        'prices' => [
                            'DKK' => $row["ddk"],
                            'EUR' => $row["eur"],
                            'USD' => $row["usd"],
                        ],
                    ];
                }
            }
            return $rows;
        }
}