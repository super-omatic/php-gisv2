<?php
session_start();
ob_start();
if (isset($_SESSION["denomination"]) && is_numeric($_SESSION["denomination"])) {
    $data = json_decode(file_get_contents('php://input'), true);
    if ($data == 1 || $data == -1) {
        include_once "config.php";
        include_once "utils.php";
        $pos = array_search($_SESSION["denomination"], $cfg["denominationValues"]);
        if (is_numeric($pos)) {
            if ($data == 1) {
                if ($pos < count($cfg["denominationValues"]) - 1) {
                    $pos++;
                };
            } else {
                if ($pos > 0) {
                    $pos--;
                };
            }
            $denomination = $cfg["denominationValues"][$pos];
            if (is_numeric($denomination)) {
                $_SESSION["denomination"] = $denomination;
                $response = [];
                $response["denomination"] = $_SESSION["denomination"];
                $response["balance"] = $_SESSION["balance"];
                $response["currency"] = $_SESSION["currency"];
                $response["login"] = $_SESSION["login"];

                // возвращаем данные в формате JSON
                header('Content-Type: application/json');
                echo json_encode($response);
            }
        }
    }
}
