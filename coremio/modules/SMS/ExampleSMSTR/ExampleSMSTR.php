<?php
    defined('CORE_FOLDER') OR exit('You can not get in here!');
    class ExampleSMSTR
    {
        public $international=false,$lang,$config;
        private $instance,$title,$body,$numbers=[],$numbers_intl=[];
        public  $error,$prevent_transmission_to_intl = false;

        public function __construct($external_config=[])
        {
            if(!class_exists("ExampleSMSTR_API")) include __DIR__.DS."api.php";
            $this->lang         = Modules::Lang("SMS",__CLASS__);
            $config             = Modules::Config("SMS",__CLASS__);
            $this->config       = $config;
            $external           = $external_config ? $external_config : [];
            $config             = array_merge($config,$external);

            $username           = $config["username"];
            $password           = $config["password"];
            $title              = $config["origin"];

            $this->instance     = new ExampleSMSTR_API();
            $this->instance->set_credentials($username,$password);

            $this->title = $title;
        }

        public function change_config($username,$password){
            $this->instance     = new ExampleSMSTR_API();
            $this->instance->set_credentials($username,$password);

            return $this;
        }

        public function title($str=''){
            $this->title = $str;
            return $this;
        }

        public function body($text='',$template=false,$variables=[],$lang='',$user=0){
            $this->numbers_reset();
            if($template) {
                $look = View::notifications("sms",$template,$text,$variables,$lang,$user);
                if($look!==false && isset($look["content"])){
                    if(isset($look["title"]))
                        $this->title($look["title"]);
                    $text = $look["content"];
                }
            }

            if(!class_exists("Money")) Helper::Load("Money");
            $currencies = Money::getCurrencies();

            foreach($currencies AS $row){
                if(($row["prefix"] && substr($row["prefix"],-1,1) == ' ') || ($row["suffix"] && substr($row["suffix"],0,1) == ' '))
                    $code = $row["code"];
                else
                    $code = $row["prefix"] ? $row["code"].' ' : ' '.$row["code"];

                $row["prefix"] = Utility::text_replace($row["prefix"],[' ' => '']);
                $row["suffix"] = Utility::text_replace($row["suffix"],[' ' => '']);
                if(!Validation::isEmpty($row["prefix"]) && $row["prefix"])
                    $text = Utility::text_replace($text,[$row["prefix"] => $code]);
                elseif(!Validation::isEmpty($row["suffix"]) && $row["suffix"])
                    $text = Utility::text_replace($text,[$row["suffix"] => $row["code"]]);
            }
            $text       = Filter::transliterate($text);

            $this->body = $text;
            return $this;
        }

        public function AddNumber($arg=0,$cc=NULL){
            if(!is_array($arg)){
                if($cc) $arg = [$cc."|".$arg];
                else $arg = [$arg];
            }
            foreach($arg AS $num){
                if(strstr($num,"|")){
                    $split  = explode("|",$num);
                    $cc     = $split[0] ? $split[0] : "90";
                    $no     = substr(Filter::numbers($split[1]),0,10);
                    $num    = $cc.$no;
                    if(!$this->prevent_transmission_to_intl && ($cc != "90" || $cc != "+90")) $this->numbers_intl[] = $num;
                    else $this->numbers[] = $num;
                }else{
                    $num    = Filter::numbers($num);
                    $strlen = strlen($num);
                    if($strlen == 11) $num = "9".$num;
                    elseif($strlen == 10) $num = "90".$num;
                    $this->numbers[] = $num;
                }
            }
            return $this;
        }

        public function submit($return_this=false){
            if(Validation::isEmpty($this->body)){
                $this->error = "Message content can not be left blank!";
                return false;
            }

            if(!$this->numbers && !$this->numbers_intl){
                $this->error = "Enter the phone number to be sent.";
                return false;
            }

            if(!$this->prevent_transmission_to_intl && $this->numbers_intl){
                if($module_intl = Config::get("modules/sms-intl")){
                    if($module_intl != "none"){
                        Modules::Load("SMS",$module_intl);
                        if(class_exists($module_intl)){
                            $sms = new $module_intl();
                            $sms->body($this->getBody())->AddNumber($this->numbers_intl);
                            $send = $sms->submit();
                            if(!$this->numbers){
                                $this->error = $sms->getError();
                                return ($return_this) ? $this : $send;
                            }
                        }
                    }
                }
            }

            if($this->numbers){
                $send = $this->instance->Submit($this->title,$this->body,$this->numbers);
                $this->error = $this->instance->error;
                return ($return_this) ? $this : $send;
            }

        }

        public function getReportID(){
            return $this->instance->rid;
        }

        public function getReport($id=0){
            $id     = ($id == 0) ? $this->getReportID() : $id;
            $content = $this->instance->ReportLook($id);
            if($content){

                $waiting_arr	    = $content["waiting"];
                $conducted_arr      = $content["conducted"];
                $erroneous_arr	    = $content["erroneous"];
                $waiting_count	    = !$waiting_arr ? 0 : sizeof($waiting_arr);
                $conducted_count	= !$conducted_arr ? 0 : sizeof($conducted_arr);
                $erroneous_count	= !$erroneous_arr ? 0 : sizeof($erroneous_arr);
                return [
                    'waiting'       => ['data' => $waiting_arr, 'count' => $waiting_count],
                    'conducted'     => ['data' => $conducted_arr, 'count' => $conducted_count],
                    'erroneous'     => ['data' => $erroneous_arr, 'count' => $erroneous_count],
                ];
            }
            return false;
        }

        public function getBalance(){
            $balance = $this->instance->Balance();

            if(!$balance && $this->instance->error){
                $this->error = $this->instance->error;
                return false;
            }
            return $balance;
        }


        public function getNumbers(){
            return array_merge($this->numbers,$this->numbers_intl);
        }

        public function getTitle(){
            return $this->title;
        }

        public function getBody(){
            return $this->body;
        }

        public function getError(){
            return $this->error;
        }

        public function numbers_reset(){
            $this->numbers      = array();
            $this->numbers_intl = array();
            return true;
        }

    }
