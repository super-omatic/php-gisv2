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
    $data = get_freerounds_stat($_SESSION["id"], $cfg);
} else {
    $data["err"] = "no user id";
}
// возвращаем данные в формате JSON
header('Content-Type: application/json');
echo json_encode($data);