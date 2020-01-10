<?php
    $credit     = $module->getBalance();
    if(!$credit) $credit = 0;
    if($credit){
        Helper::Load("Money");
        $credit = Money::formatter_symbol($credit["balance"],$credit["currency"]);
    }

    echo Utility::jencode([
        'credit' => $credit
    ]);