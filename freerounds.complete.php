<?php
include_once "config.php";
include_once "gis_api.php";
include_once "db.php";
include_once "utils.php";

//gis_write_error("freerounds.complete", $cfg);
// получим данные от Платформы
$data = json_decode(file_get_contents('php://input'), true);
//gis_write_error(json_encode($data), $cfg);
// проверим, что мы получили id фрираундов
if (is_array($data) && isset($data["freerounds_id"]) && $data["freerounds_id"]) {
// проверим, что мы получили значение сессии
    if (is_array($data) && isset($data["session"]) && $data["session"]) {
        if (isset($data["currency"]) && $data["currency"]) {
            if (isset($data["trx_id"]) && $data["trx_id"]) {
                if (isset($data["amount"]) && is_numeric($data["amount"])) {
                    if (gis_check_sign($data, "freerounds.complete", $cfg)) {
                        $response = process_trx(
                            clean_string($data["trx_id"]),
                            "DEPOSIT",
                            "FREEROUNDS",
                            intval($data["amount"]),
                            clean_string($data["currency"]),
                            clean_string($data["session"]),
                            $cfg
                        );

                        // завершение транзакции успешно
                        if (is_array($response)) {
                            // отметим фрираунды как законченые
                            if (!set_freerounds_complete(clean_string($data["freerounds_id"]), $cfg)) {
                                $response = "amount updated, but free rounds not closed";
                            }
                        }
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
} else {
    $response = "cannot process value freerounds_id";
}

header('Content-Type: application/json');

// Эмуляция случайной ошибки
if (is_debug() && rand(0, 99) < 10) {
    if (rand(0, 9) < 5) {
        gis_write_error("freerounds.complete - random 500 err", $cfg);
        http_response_code(500);
    } else {
        gis_write_error("freerounds.complete - random sleep err", $cfg);
        sleep(3);
    }
}

//gis_write_error(json_encode($response), $cfg);
if (is_array($response)) {
    // списание с баланса успешно
    echo '{"method":"freerounds.complete","status":200,"response":' . json_encode($response) . '}';
} else {
    // ошибка при списании с баланса
    gis_write_error("freerounds.complete - " . clean_string($response), $cfg);
    echo '{"method":"freerounds.complete","status":500,"message":"' . clean_string($response) . '"}';
}
