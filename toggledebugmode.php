<?php
include_once("config.php");
$debug_file_name = "debug.lock";

$data = [
    "error" => "unhandled error",
    "success" => false
];
if (!$cfg["isProduction"]) {
    if (file_exists($debug_file_name)) {
        if (unlink($debug_file_name)) {
            $data["success"] = true;
            $data["debug"] = false;
            unset($data["error"]);
        } else {
            $data["error"] = "cannot remove " . $debug_file_name;
        }
    } else {
        if (touch($debug_file_name)) {
            $data["success"] = true;
            $data["debug"] = true;
            unset($data["error"]);
        } else {
            $data["error"] = "cannot create " . $debug_file_name;
        }
    }
} else {
    $data["error"] = "debug in production mode";
}


// возвращаем данные в формате JSON
header('Content-Type: application/json');
echo json_encode($data);