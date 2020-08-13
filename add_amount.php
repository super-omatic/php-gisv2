<?php
session_start();
ob_start();
include_once "config.php";
include_once "db.php";
include_once "gis_api.php";

// ответ с данными мы вернем в любом случае
$data = [
    "err" => "unhandled error",
    "success" => false
];

if (isset($_SESSION["id"]) && $_SESSION["id"]) {
    $data = add_amount($_SESSION["id"], 10000, $cfg);
    if ($data['success']) {
        $update = update_user($_SESSION["id"], $cfg);
        if ($update) {
            $_SESSION = $update;
        }
        $data['login'] = $_SESSION['login'];
        $data['balance'] = $_SESSION['balance'];
        $data['currency'] = $_SESSION['currency'];
        $data['denomination'] = $_SESSION['denomination'];
    }

} else {
    $data["err"] = "no user id";
}
// возвращаем данные в формате JSON
header('Content-Type: application/json');
echo json_encode($data);