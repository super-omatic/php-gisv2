<?php
include_once "config.php";
include_once "gis_api.php";
include_once "db.php";
include_once "utils.php";

//gis_write_error("freerounds.step", $cfg);
// получим данные от Платформы
$data = json_decode(file_get_contents('php://input'), true);
//gis_write_error(json_encode($data), $cfg);

// проверим, что мы получили id фрираундов
if (is_array($data) && isset($data["freerounds_id"]) && $data["freerounds_id"]) {
    if (gis_check_sign($data, "freerounds.step", $cfg)) {
        $response = inc_freerounds_step(clean_string($data["freerounds_id"]), $cfg);
    } else {
        $response = "wrong sign";
    }
} else {
    $response = "cannot process value freerounds_id";
}

header('Content-Type: application/json');

// Эмуляция случайной ошибки
if (is_debug() && rand(0, 99) < 10) {
    if (rand(0, 9) < 5) {
        gis_write_error("freerounds.step - random 500 err", $cfg);
        http_response_code(500);
    } else {
        gis_write_error("freerounds.step - random sleep err", $cfg);
        sleep(3);
    }
}

//gis_write_error(json_encode($response), $cfg);
if ($response === true) {
    // списание с баланса успешно
    echo '{"method":"freerounds.step","status":200,"response":' . json_encode($response) . '}';
} else {
    // ошибка при списании с баланса
    gis_write_error("freerounds.step - " . clean_string($response), $cfg);
    echo '{"method":"freerounds.step","status":500,"message":"' . clean_string($response) . '"}';
}
