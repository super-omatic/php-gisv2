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
    $data = add_free_rounds($_SESSION["id"], $_POST["count"], $cfg);
    if ($data['success']) {
        $data['freerounds'] = get_freerounds_count($data['freerounds_id'], $cfg);
    }
} else {
    $data["err"] = "no user id";
}
// возвращаем данные в формате JSON
header('Content-Type: application/json');
echo json_encode($data);