<?php
    if(!defined("CORE_FOLDER")) die();

    $lang           = $module->lang;
    $config         = $module->config;


    $username       = (string) Filter::init("POST/username","hclear");
    $password       = (string) Filter::init("POST/password","hclear");
    $origin         = (string) Filter::init("POST/origin","hclear");


    $sets       = [];

    if($username != $config["username"]) $sets["username"] = $username;
    if($password != $config["password"]) $sets["password"] = $password;
    if($origin != $config["origin"]) $sets["origin"] = $origin;


    if($sets){
        $config_result  = array_replace_recursive($config,$sets);
        $array_export   = Utility::array_export($config_result,['pwith' => true]);
        $file           = dirname(__DIR__).DS."config.php";
        $write          = FileManager::file_write($file,$array_export);

        $adata          = UserManager::LoginData("admin");
        User::addAction($adata["id"],"alteration","changed-sms-module-settings",[
            'module' => $config["meta"]["name"],
            'name'   => $lang["name"],
        ]);
    }

    echo Utility::jencode([
        'status' => "successful",
        'message' => $lang["success1"],
    ]);