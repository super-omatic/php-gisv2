<?php
include_once "config.php";
include_once "gis_api.php";
include_once "db.php";
include_once "utils.php";

//gis_write_error("withdraw.bet", $cfg);
// получим данные от Платформы
$data = json_decode(file_get_contents('php://input'), true);
// проверим, что мы получили значение сессии
if (is_array($data) && isset($data["session"]) && $data["session"]) {
    if (isset($data["currency"]) && $data["currency"]) {
        if (isset($data["trx_id"]) && $data["trx_id"]) {
            if (isset($data["amount"]) && is_numeric($data["amount"])) {
                if (gis_check_sign($data, "withdraw.bet", $cfg)) {
                    $response = process_trx(
                        clean_string($data["trx_id"]),
                        "WITHDRAW",
                        "NORMAL",
                        -intval($data["amount"]),
                        clean_string($data["currency"]),
                        clean_string($data["session"]),
                        $cfg
                    );
                } else {
                    $response = "wrong sign";
                }
            } else {
                $response = "cannot process value amount";
            }
        } else {
            $response = "cannot process value trx_id";
        }
    } else {
        $response = "cannot process value currency";
    }
} else {
    $response = "cannot process value session";
}
header('Content-Type: application/json');

// Эмуляция случайной ошибки
if (is_debug() && rand(0, 99) < 10) {
    if (rand(0, 9) < 5) {
        gis_write_error("withdraw.bet - random 500 err", $cfg);
        http_response_code(500);
    } else {
        gis_write_error("withdraw.bet - random sleep err", $cfg);
        sleep(3);
    }
}

//gis_write_error(json_encode($response), $cfg);
if (is_array($response)) {
    // списание с баланса успешно
    echo '{"method":"withdraw.bet","status":200,"response":' . json_encode($response) . '}';
} else {
    // ошибка при списании с баланса
    gis_write_error("withdraw.bet - " . clean_string($response), $cfg);
    echo '{"method":"withdraw.bet","status":500,"message":"' . clean_string($response) . '"}';
}
