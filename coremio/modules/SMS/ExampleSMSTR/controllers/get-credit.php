<?php
    $credit     = $module->getBalance();
    if(!$credit) $credit = 0;

    echo Utility::jencode([
        'credit' => $credit
    ]);