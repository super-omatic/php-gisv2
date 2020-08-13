<?php
include_once "config.php";
include_once "gis_api.php";
include_once "db.php";
include_once "utils.php";

//gis_write_error("check.balance", $cfg);
// получим данные от Платформы
$data = json_decode(file_get_contents('php://input'), true);
// проверим, что мы получили значение сессии
if (is_array($data) && isset($data["session"]) && $data["session"]) {
    if (isset($data["currency"]) && $data["currency"]) {
        if (gis_check_sign($data, "check.balance", $cfg)) {
            $response = check_balance(clean_string($data["session"]), clean_string($data["currency"]), $cfg);
        } else {
            $response = "wrong sign";
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
        gis_write_error("check.balance - random 500 err", $cfg);
        http_response_code(500);
    } else {
        gis_write_error("check.balance - random sleep err", $cfg);
        sleep(3);
    }
}

//gis_write_error(json_encode($response), $cfg);
if (is_array($response)) {
    // проверка сессии успешна
    echo '{"method":"check.balance","status":200,"response":' . json_encode($response) . '}';
} else {
    // ошибка при проверке сессии
    gis_write_error("check.balance - " . clean_string($response), $cfg);
    echo '{"method":"check.balance","status":500,"message":"' . clean_string($response) . '"}';
}
