<?php
session_start();
ob_start();

if (isset($_SESSION['id'])) {
    include_once "config.php";
    include_once "db.php";
    include_once "gis_api.php";
    $unfinished_freerounds = get_unfinished_freerounds($_SESSION['id'], $cfg);
    if (!empty($unfinished_freerounds)) {
        echo "<h3>You have unfinished freerounds</h3>";

        // запрос игр через GIS API
        $jsonArray = gis_games($cfg);
        if (is_array($jsonArray)) {
            $games = $jsonArray["response"];

            foreach ($unfinished_freerounds as $item) {
                // получим данные по фрираундам
                $requestParams = [
                    'partner.alias' => $cfg["partner.alias"], // Ваш идентификатор Партнера
                    'freerounds.id' => $item['id'] // Ваш идентификатор сессии игрока
                ];

                $sign = make_sign($requestParams, "games.freeroundsInfo", $cfg);
                $requestParams['sign'] = $sign;

                $jsonArray = gis_freerounds_info($requestParams, $cfg);
                if ($jsonArray['status'] === 200) {

                    $game_name = $item['platform_game_id'];
                    foreach ($games as $key => $value) {
                        if ($value['id'] == $game_name) {
                            $game_name = $value['title'];
                            break;
                        }
                    }

                    echo "<span>" . $game_name . " - played " . $jsonArray['response']['steps'] . " of " . $jsonArray['response']['max_step'] . ", uncommitted prize " . $jsonArray['response']['total_win'] . " " . $item['currency'] . "</span><br />";
                }
            }
        }

        echo "<hr>";
    }
}

echo "";
