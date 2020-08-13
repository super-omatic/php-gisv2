<?php
session_start();
ob_start();

include_once "config.php";
include_once "db.php";
include_once "gis_api.php";

$data = [];

// выход из игры
if (isset($_SESSION['id'])) {
    $data['success'] = true;

    $gameSession = get_current_session_id($_SESSION['id'], $cfg);
    // требуется ли закрытие сессии
    if (isset($_GET["hc"]) && $gameSession) {
        // закроем сессию
        $requestParams = [
            'partner.alias' => $cfg["partner.alias"], // Ваш идентификатор Партнера
            'partner.session' => $gameSession // Ваш идентификатор сессии игрока
        ];

        $sign = make_sign($requestParams,"close.session", $cfg);
        $requestParams['sign'] = $sign;

        // раскомментировать для проверки олтправляемой строки
        //gis_write_error(json_encode($requestParams), $cfg);
        $jsonArray = gis_close_session($requestParams, $cfg);
    }

    if (isset($jsonArray)) {
        $data['success'] = $jsonArray['status'] == 200;
        if (isset($jsonArray['message'])) $data['error'] = $jsonArray['message'];
        void_current_freerounds($_SESSION['id'], $cfg);
    }

    if ($data['success']) {
        // если есть активный пользователь, то выход нужно записать в базе
        // CLOSED - сессия закрыта и в интеграции, и на Платформе
        // STOPPED - сессия закрыта только в интеграции
        if (close_current_session($_SESSION['id'], isset($jsonArray) ? "CLOSED" : "STOPPED",  $cfg)) {
            $update = update_user($_SESSION["id"], $cfg);
            if ($update) {
                $_SESSION = $update;
            }
        } else {
            // ошибка выхода из игры
            $data['integrationError'] = "Game session closed with error";
        }
    }

    $data['login'] = $_SESSION['login'];
    $data['balance'] = $_SESSION['balance'];
    $data['currency'] = $_SESSION['currency'];
    $data['denomination'] = $_SESSION['denomination'];
}

// возвращаем данные в формате JSON
header('Content-Type: application/json');
echo json_encode($data);

