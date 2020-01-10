<?php
    $LANG           = $module->lang;
    $order          = isset($order) && $order ? $order : [];
    $options        = isset($order["options"]) ? $order["options"] : [];
    $config         = isset($options["config"]) ? $options["config"] : [];
    $username       = isset($config["username"]) ? $config["username"] : '';
    $password       = isset($config["password"]) ? $config["password"] : '';
    $credit         = 0;
    $error          = false;
    if($username && $password){
        $module->change_config($username,$password);
        $getCredit  = $module->getBalance();
        if(!$getCredit && $module->error) $error = $module->error;
        else $credit = $getCredit;
    }
?>
<div class="formcon">
    <div class="yuzde30">Modül Kullanıcı Adı</div>
    <div class="yuzde70">
        <input type="text" name="config[username]" value="<?php echo $username; ?>">
    </div>
</div>

<div class="formcon">
    <div class="yuzde30">Modül Parola</div>
    <div class="yuzde70">
        <input type="text" name="config[password]" value="<?php echo $password; ?>">
    </div>
</div>

<div class="formcon">
    <div class="yuzde30">Bakiye Bilgisi</div>
    <div class="yuzde70">
        <?php
            echo $error ? $error : $credit;
        ?>
    </div>
</div>