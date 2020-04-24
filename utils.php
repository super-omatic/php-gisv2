<?php

function double_formatter($amount, $minor_units)
{
    $amount = strval($amount);
    while (strlen($amount) <= $minor_units) {
        $amount = "0" . $amount;
    }
    return substr($amount, 0, -$minor_units) . "." . substr($amount, -$minor_units);
}

function is_only_valid_symbols($str){
    return preg_match("#^[aA-zZ0-9\-_]+$#",$str);
}
