<?php
    class ExampleSMSTR_API {
        private $username,$password,$url,$timeout;
        public $error,$rid;


        public function __construct() {
            $this->url 	        = "https://example-provider.com/api/v1/";
            $this->timeout      = 200;
        }

        public function set_credentials($username, $password){
            $this->username = $username;
            $this->password = $password;
        }

        private function call($command='',$data = []){
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL,$this->url.$command);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
            $result = curl_exec($ch);

            if(curl_errno($ch)){
                $this->error = curl_error($ch);
                return false;
            }

            $result     = json_decode($result,true);

            if(!$result){
                $this->error = 'The incoming API response could not be resolved.';
                return false;
            }

            if(isset($result["error"]) && $result["error"]){
                $this->error = $result["error"];
                return false;
            }

            return $result;
        }

        public function Balance(){
            $data = [
                'username' => $this->username,
                'password' => $this->password,
            ];

            $response = $this->call("balance",$data);
            if(!$response) return false;

            return $response["credit"];
        }


        public function Submit($sender_name = NULL,$message = NULL,$numbers = 0){
            if(!is_array($numbers)) $numbers = array($numbers);

            $data = [
                'username' => $this->username,
                'password' => $this->password,
                'numbers'  => $numbers,
                'sender'   => $sender_name,
            ];


            $response = $this->call("send",$data);
            if(!$response) return false;

            $this->rid = $response["ReportID"];

            return true;
        }

        public function ReportLook($rid){
            $data = [
                'username' => $this->username,
                'password' => $this->password,
                'ReportID' => $rid,
            ];
            $report = $this->call("report",$data);

            if(isset($report["data"]) && $report["data"]){
                $list           = $report["data"];
                $conducted      = [];
                $waiting        = [];
                $erroneous      = [];

                foreach($list AS $row){
                    if($row){
                        $status     = $row["status"];
                        $phone      = $row["phone"];
                        if($status == "delivered")
                            $conducted[] = $phone;
                        elseif($status == "enroute")
                            $waiting[] = $phone;
                        else
                            $erroneous[] = $phone;
                    }
                }
                return [
                    'conducted'     => $conducted,
                    'waiting'       => $waiting,
                    'erroneous'     => $erroneous,
                ];
            }else{
                $this->error = "No results from the API";
                return false;
            }

            return false;
        }
    }