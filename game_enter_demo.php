<?php
session_start();
ob_start();
$err_msg = "unhandled error in game_enter";
if (isset($_GET['id']) && is_numeric($_GET['id'])) {

    include_once "config.php";
    include_once "db.php";
    include_once "gis_api.php";

    if (isset($_SESSION['denomination']) && is_numeric($_SESSION['denomination'])) {
        $denomination = $_SESSION['denomination']; // деноминация сессии
    } else {
        $denomination = $cfg["denomination"];
    }

    // параметры игровой сессии
    $requestParams = [
        "game.id" => $_GET['id'], // игра в которую заходим
        "currency" => $cfg["demoCurrency"], // валюта демо-игры
        "balance" => $cfg["demoBalance"] = 100000, // стартовый баланс
        "denomination" => $denomination // деноминация задаваемая партнером
    ];
    // переключимся на игру
    $jsonArray = gis_init_demo($requestParams, $cfg);
    if (is_array($jsonArray) && $jsonArray["status"] == 200) {
        $data = [];
        $data['iframe'] = '<iframe onload="initIFrameFocus(this)" class="w-100 h-100" src="' . $jsonArray["response"]["clientDist"] . "?t=" . $jsonArray["response"]["token"] . '"></iframe>';
        $data['clientDist'] = $jsonArray["response"]["clientDist"];
        $data['token'] = $jsonArray["response"]["token"];

        // возвращаем данные в формате JSON
        header('Content-Type: application/json');
        echo json_encode($data);
        exit(0); // successfully stop script
    } else {
        // Платформа вернула статус отличный от 200
        $err_msg = "Platform error, see log";
    }
} else {
    $err_msg = "Game id not defined"; // номер игры обязателен
}
echo "<div class='alert alert-danger'>" . $err_msg . "</div>";
