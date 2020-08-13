<?php
include_once "config.php";
include_once "gis_api.php";
include_once "db.php";
include_once "utils.php";

gis_write_error("trx.cancel", $cfg);
// получим данные от Платформы
$data = json_decode(file_get_contents('php://input'), true);
//gis_write_error(json_encode($data), $cfg);
// проверим, что мы получили значение сессии
if (is_array($data) && isset($data["session"]) && $data["session"]) {
    if (isset($data["currency"]) && $data["currency"]) {
        if (isset($data["trx_id"]) && $data["trx_id"]) {
            if (isset($data["amount"]) && is_numeric($data["amount"])) {
                if (gis_check_sign($data, "trx.cancel", $cfg)) {
                    $response = cancel_trx(
                        clean_string($data["trx_id"]),
                        intval($data["amount"]),
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
        gis_write_error("trx.cancel - random 500 err", $cfg);
        http_response_code(500);
    } else {
        gis_write_error("trx.cancel - random sleep err", $cfg);
        sleep(3);
    }
}

//gis_write_error(json_encode($response), $cfg);
if (is_array($response)) {
    // отмена транзакции успешна
    echo '{"method":"trx.cancel","status":200,"response":' . json_encode($response) . '}';
} else {
    // ошибка отмены транзакции
    gis_write_error("trx.cancel - " . clean_string($response), $cfg);
    echo '{"method":"trx.cancel","status":500,"message":"' . clean_string($response) . '"}';
}