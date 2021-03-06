<?php
session_start();
ob_start();

$err_msg = "unhandled error in game_enter";
// если пользователь разлогинился в другой вкладке
if (isset($_SESSION["id"])) {
    if (isset($_GET['id']) && is_numeric($_GET['id'])) {

        include_once "config.php";
        include_once "db.php";
        include_once "gis_api.php";

        $playerId = $_SESSION["id"]; // номер игрока в Вашей системе
        $currency = $_SESSION["currency"]; // валюта игрока
        $platformGameId = $_GET['id']; // игра в которую заходим
        $denomination = $_SESSION['denomination']; // деноминация сессии

        // создадим новую сессию, только если нет активных
        if (is_new_game_session_available($playerId, $_SESSION["sid"], $cfg)) {

            // проверим есть ли у нас незавершенные фрираунды в этой игре
            // если есть, то продолжим игровую сессию не начиная новую
            if ($jsonArray = get_game_config_with_unfinished_freerounds($playerId, $platformGameId, $cfg)) {
                $data = [];
                $data['iframe'] = '<iframe onload="initIFrameFocus(this)" class="w-100 h-100" src="' . $jsonArray["clientDist"] . "?t=" . $jsonArray["token"] . '"></iframe>';
                $data['clientDist'] = $jsonArray["clientDist"];
                $data['token'] = $jsonArray["token"];

                // возвращаем данные в формате JSON
                header('Content-Type: application/json');
                echo json_encode($data);
                exit(0); // successfully stop script
            } else {
                //если есть фрираунды - получим их id и количество
                $freerounds_id = get_free_rounds_to_activate($playerId,  $cfg);
                $freerounds_total = 0;
                if ($freerounds_id) {
                    $unclosed_session = get_last_game_unclosed_session($playerId, $platformGameId, $cfg);
                    // запустить фрираунды можно только для игры, где нет незакрытой На платформе сессии
                    // если игра не закрыта на Платформе - закроем её забрав призы
                    if ($unclosed_session) {
                        if (reactivate_stopped_game_session($playerId, $unclosed_session, $cfg)) {
                            // закроем сессию
                            $requestParams = [
                                'partner.alias' => $cfg["partner.alias"], // Ваш идентификатор Партнера
                                'partner.session' => $unclosed_session // Ваш идентификатор сессии игрока
                            ];
                            $sign = make_sign($requestParams,"close.session", $cfg);
                            $requestParams['sign'] = $sign;
                            gis_close_session($requestParams, $cfg);
                            if (close_current_session($_SESSION['id'], "CLOSED",  $cfg)) {
                                $update = update_user($_SESSION["id"], $cfg);
                                if ($update) {
                                    $_SESSION = $update;
                                }
                            }
                        }
                    }

                    $freerounds_total = get_freerounds_count($freerounds_id, $cfg);
                }

                // регистрируем сессию в нашей системе
                $gameSession = create_game_session($playerId, $platformGameId, $currency, $denomination, $cfg);
                if ($gameSession) {
                    // параметры игровой сессии
                    $requestParams = [
                        'partner.alias' => $cfg["partner.alias"], // Ваш идентификатор Партнера
                        'partner.session' => $gameSession, // Ваш идентификатор сессии игрока
                        'game.id' => $platformGameId, // игра в которую заходим
                        'currency' => $currency // валюта игры
                    ];

                    // добавим id фрираундов в запрос
                    if ($freerounds_id) $requestParams['freerounds.id'] = $freerounds_id; // ПАРАМЕТР ЗАПРОСА ЧЕРЕЗ ТОЧКУ!

                    // переключимся на игру
                    $jsonArray = gis_init($requestParams, $cfg);
                    if (is_array($jsonArray) && $jsonArray["status"] == 200) {
                        // активируем игровую сессию
                        activate_game_session($playerId, $gameSession, $jsonArray["response"], $cfg);
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
                    if ($freerounds_id) deactivate_freerounds($freerounds_id, $cfg);
                    close_session_with_error($gameSession, $cfg);
                } else {
                    // нет возможности записать сессию в базу или получить её
                    $err_msg = "Can't create local game session";
                }
            }
        } else {
            $err_msg = "Session id error. Please, <a href='index.php'>reload</a>";
        }
    } else {
        $err_msg = "Game id not defined"; // номер игры обязателен
    }
} else {
    $err_msg = "You had logout. <a href='/login_form.php'>Sign in</a> to start play";
}
echo "<div class='alert alert-danger'>" . $err_msg . "</div>";
