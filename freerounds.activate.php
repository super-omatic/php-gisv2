<?php
include_once "config.php";
include_once "gis_api.php";
include_once "db.php";
include_once "utils.php";

//gis_write_error("freerounds.activate", $cfg);
// получим данные от Платформы
$data = json_decode(file_get_contents('php://input'), true);
//gis_write_error(json_encode($data), $cfg);

// проверим, что мы получили значение сессии
if (is_array($data) && isset($data["session"]) && $data["session"]) {
    // проверим, что мы получили id фрираундов
    if (isset($data["freerounds_id"]) && $data["freerounds_id"]) {
        if (gis_check_sign($data, "freerounds.activate", $cfg)) {
            // активируем фрираунды
            if (set_freerounds_active(clean_string($data["freerounds_id"]), clean_string($data["session"]), $cfg)) {
                $response = [];
                $response['total'] = get_freerounds_count(clean_string($data["freerounds_id"]), $cfg);
            } else {
                $response = "cannot activate freerounds";
            }
        } else {
            $response = "wrong sign";
        }
    } else {
        $response = "cannot process value freerounds_id";
    }
} else {
    $response = "cannot process value session";
}

header('Content-Type: application/json');

// Эмуляция случайной ошибки
if (is_debug() && rand(0, 99) < 10) {
    if (rand(0, 9) < 5) {
        gis_write_error("freerounds.activate - random 500 err", $cfg);
        http_response_code(500);
    } else {
        gis_write_error("freerounds.activate - random sleep err", $cfg);
        sleep(3);
    }
}

//gis_write_error(json_encode($response), $cfg);
if (is_array($response)) {
    // списание с баланса успешно
    echo '{"method":"freerounds.activate","status":200,"response":' . json_encode($response) . '}';
} else {
    // ошибка при списании с баланса
    gis_write_error("freerounds.activate - " . clean_string($response), $cfg);
    echo '{"method":"freerounds.activate","status":500,"message":"' . clean_string($response) . '"}';
}
