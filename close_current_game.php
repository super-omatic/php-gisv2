<?php
session_start();
ob_start();

include_once "config.php";
include_once "db.php";
include_once "gis_api.php";

$data = [];

// выход из игры
if (isset($_SESSION['id'])) {
    // если есть активный пользователь, то выход нужно записать в базе
    if (close_current_session($_SESSION['id'], $cfg)) {
        $update = update_user($_SESSION["id"], $cfg);
        if ($update) {
            $_SESSION = $update;
        }
    } else {
        // ошибка выхода из игры
        $data['err'] = "Game session closed with error";
    }
    $data['login'] = $_SESSION['login'];
    $data['balance'] = $_SESSION['balance'];
    $data['currency'] = $_SESSION['currency'];
    $data['denomination'] = $_SESSION['denomination'];
}

// возвращаем данные в формате JSON
header('Content-Type: application/json');
echo json_encode($data);

