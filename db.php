<?php
/**
 * создать соединение с базой данных
 * @param $cfg - глобальные настройки
 * @return false|mysqli - mysqli ссылка для работы с базой
 */
function db_connect($cfg)
{
    // попробуем установить соединение с базой данных
    $link = mysqli_connect($cfg["hostname"], $cfg["username"], $cfg["password"])
    OR DIE("<div class='alert alert-danger'>Error: cannot make connection to DataBase host<br/>"
        . 'Check "hostname", "username" and "password" are correct in <b>config.php</b><br/><i>'
        . mysqli_error($link) . "</i></div>");

    //выбрать базу данных. Если произойдет ошибка - вывести ее
    mysqli_select_db($link, $cfg["dbName"]) or die("<div class='alert alert-danger'>Error: cannot make connection to " . $cfg["dbName"] . "<br/>"
        . 'Check "dbName" is correct in <b>config.php</b><br/><i>'
        . mysqli_error($link) . "</i></div>");

    // сообщим базе, что будем использовать кодировку utf8
    mysqli_query($link, "SET NAMES 'utf8'");
    // mysqli ссылка для работы с базой
    return $link;
}

/**
 * простая очистка от SQL-иньекций
 * @param $s - потенциально опасная строка
 * @return string - строка очищенная от популярных вариантов SQL-иньекций
 */
function clean_string($s)
{
    $trashSymbols = array("%", "`", "'", '"', "..", ",", "(", ")", "#", "№", ":", ";");
    return trim(strip_tags(stripslashes(str_replace($trashSymbols, "",
        str_replace("\n", ' ', str_replace("|", '_',
            htmlspecialchars($s, ENT_QUOTES)))))));
}

/**
 * Проверка возможности открыть игровую сессию
 * @param $user_id - номер пользователя
 * @param $sid - идентификатор сессии пользователя
 * @param $cfg - глобальные настройки
 * @return bool
 */
function is_new_game_session_available($user_id, $sid, $cfg)
{
    $link = db_connect($cfg);
    $query = "SELECT id FROM users WHERE sid = '" . $sid . "' AND id=" . $user_id;
    $res = mysqli_query($link, $query) or die(mysqli_error($link));
    if ($row = mysqli_fetch_array($res)) {
        $query = "SELECT status FROM game_sessions WHERE user_id=" . $user_id . " ORDER BY date DESC LIMIT 1";
        $res = mysqli_query($link, $query) or die(mysqli_error($link));
        if ($row = mysqli_fetch_array($res)) {
            if ($row["status"] == "ACTIVE") {
                return false;
            }
        }
        return true;
    }
    return false;
}

/**
 * Регистрация в системе новой игровой сессии
 * @param $user_id - номер пользователя
 * @param $game_id - идентификатор игры на стороне Платформы
 * @param $currency - валюта игровой сессии
 * @param $denomination - деноминация игровой сессии
 * @param $cfg - глобальные настройки
 * @return string
 */
function create_game_session($user_id, $game_id, $currency, $denomination, $cfg)
{
    $link = db_connect($cfg);

    // сгенерируем номер игровой сессии
    $session_id = md5(uniqid(mt_rand(), true));
    $query = "INSERT INTO `game_sessions` (id, user_id, platform_game_id, denomination, currency) VALUES ('"
        . $session_id . "'," . $user_id . "," . $game_id . "," . $denomination . ",'" . $currency . "')";
    mysqli_query($link, $query) or die(mysqli_error($link));

    // вернем сгенерированный номер
    return $session_id;
}

/**
 * Активация игровой сессии
 * Единовременно у игрока может быть только одна сессия
 * @param $user_id - номер пользователя
 * @param $current_session - номер активируемой сессии
 * @param $response - массив с ответом Платформы
 * @param $cfg - глобальные настройки
 * @return bool - Успешно ли прошла активация
 */
function activate_game_session($user_id, $current_session, $response, $cfg)
{
    $link = db_connect($cfg);

    // запросим данные по пользователю
    $query = "SELECT id, balance, currency FROM users WHERE `current_session` IS NULL AND `id`=" . $user_id;
    $res = mysqli_query($link, $query) or die(mysqli_error($link));
    if ($row = mysqli_fetch_array($res)) {
        // активируем сессию
        $query = "UPDATE `game_sessions` SET `balance` = " . $row["balance"] .
            ", `startAmount` = " . $row["balance"] .
            ", `status` = 'ACTIVE'" .
            ", `game_config` = '" . str_replace("'", '', json_encode($response)) . "'" .
            " WHERE `id` = '" . $current_session . "'" .
            " AND `user_id` = " . $user_id .
            " AND `status` = 'CHECKED'" .
            " AND `currency` = '" . $row["currency"] . "'";
        mysqli_query($link, $query) or die(mysqli_error($link));
        // обновление успешно?
        if (mysqli_affected_rows($link) == 1) {
            // обновим активную сессию пользователя
            $query = "UPDATE `users` SET balance = 0, current_session = '" . $current_session . "' WHERE id = " . $user_id;
            mysqli_query($link, $query) or die(mysqli_error($link));
            // обновление успешно?
            if (mysqli_affected_rows($link) == 1) {
                return true;
            }
        }
    }
    return false;
}

/**
 * Проставить сессии ошибку
 * @param $session_id - идентификатор сессии
 * @param $cfg - глобальный настройки
 */
function close_session_with_error($session_id, $cfg)
{
    $link = db_connect($cfg);
    // закроем сессию
    $query = "UPDATE `game_sessions` SET status = 'ERROR' WHERE id = '" . $session_id . "'";
    mysqli_query($link, $query) or die(mysqli_error($link));
}

/**
 * Закрытие активной сессии игрока
 * @param $user_id - номер пользователя
 * @param $cfg - глобальные настройки
 * @return bool
 */
function close_current_session($user_id, $cfg)
{
    $link = db_connect($cfg);

    // получим данные по игроку
    $query = "SELECT balance, currency, current_session FROM users WHERE `id` = " . $user_id;
    $res = mysqli_query($link, $query) or die(mysqli_error($link));
    // есть ли такой пользователь
    if ($row_user = mysqli_fetch_array($res)) {
        // есть ли у пользователя активная сессия
        if ($row_user["current_session"]) {
            // если у игрока есть открытая сессия его баланс обязан быть 0,
            // т.к. весь баланс игрока обязан находиться на активной игровой сессии
            if ($row_user["balance"] == 0) {
                // запросим данные активной сессии
                // необходимо убедиться:
                // - сессия открыта (status == 'ACTIVE'),
                // - совпадают пользователи,
                // - совпадают валюты
                $query = "SELECT balance FROM game_sessions " .
                    "WHERE `status` = 'ACTIVE' AND `user_id` = " . $user_id .
                    " AND `currency` = '" . $row_user["currency"] . "' AND `id` = '" . $row_user["current_session"] . "'";
                $res = mysqli_query($link, $query) or die(mysqli_error($link));
                // есть ли такая сессия
                if ($row_game_session = mysqli_fetch_array($res)) {
                    // закроем сессию
                    $query = "UPDATE `game_sessions` SET status = 'CLOSED' WHERE id = '" . $row_user["current_session"] . "'";
                    mysqli_query($link, $query) or die(mysqli_error($link));
                    // обновление успешно?
                    if (mysqli_affected_rows($link) == 1) {
                        // уберем активную сессию пользователя и зачислим ему баланс с неё
                        $query = "UPDATE `users` SET current_session = NULL, balance = " . $row_game_session["balance"] .
                            " WHERE id = " . $user_id;
                        mysqli_query($link, $query) or die(mysqli_error($link));
                        // обновление успешно?
                        if (mysqli_affected_rows($link) == 1) {
                            return true;
                        }
                    }
                }
            }
        } else {
            // активной сессии у пользователя нет.
            // Считаем, что работа скрипта на этом успешно выполнена,
            // для избежания ошибок при повторных вызовах
            return true;
        }
    }
    return false;
}

/**
 * Добавление пользователя
 * @param $login
 * @param $password
 * @param $currency - валюта аккаунта пользователя
 * @param $cfg - глобальные настройки
 * @return false|int|string
 */
function add_user($login, $password, $currency, $cfg)
{
    $link = db_connect($cfg);
    $query = "INSERT INTO `users` (login, password, currency, balance) VALUES ('"
        . clean_string($login) . "','" . clean_string($password) . "','" . clean_string($currency) . "','" . $cfg["startBalance"] . "')";

    if (mysqli_query($link, $query)) {
        return mysqli_insert_id($link);
    } else {
        return false;
    }
}

/**
 * Подготовка массива с актуальными данными игрока
 * @param $user_id - номер пользователя
 * @param $cfg - глобальные настройки
 * @return bool | array - массив с данными игрока или false
 */
function update_user($user_id, $cfg)
{
    $link = db_connect($cfg);

    // запросим данные по пользователю
    $query = "SELECT * FROM users WHERE `id` = " . $user_id;
    $res = mysqli_query($link, $query) or die(mysqli_error($link));

    if ($row = mysqli_fetch_array($res)) {
        // пользователь найден
        if ($row["sid"] == $_SESSION["sid"]) {
            $session_array = [];
            $session_array["id"] = $row["id"];
            $session_array["login"] = $row["login"];
            $session_array["balance"] = $row["balance"];
            $session_array["currency"] = $row["currency"];
            $session_array["sid"] = $row["sid"];
            // если активной игровой сессии нет
            $session_array["gameUrl"] = '';
            if ($row["current_session"]) {
                // проверим наличие активной игровой сессии у игрока
                $query = "SELECT * FROM `game_sessions` WHERE `id` = '" . $row["current_session"] . "'";
                $res = mysqli_query($link, $query) or die(mysqli_error($link));
                if ($row = mysqli_fetch_array($res)) {
                    if ($row["status"] == "ACTIVE") {
                        // если активная игровая сессия есть
                        $game_config = json_decode($row["game_config"], true);
                        $session_array["gameUrl"] = $game_config["clientDist"] . "?t=" . $game_config["token"];
                    } else {
                        $session_array["err"] = "invalid current game session";
                    }
                }
            }

            // деноминатор
            if (isset($_SESSION["denomination"])) {
                $session_array["denomination"] = $_SESSION["denomination"];
            } else {
                $session_array["denomination"] = $cfg["denomination"];
            }

            // вернем массив с данными игрока
            return $session_array;
        } else {
            // не совпали идентификаторы сессии
            return false;
        }
    } else {
        // пользователь не найден
        return false;
    }
}

/**
 * Проверка сессии
 * @param $session_id - id инициализируемой сессии
 * @param $currency - 3-Latin код валюты
 * @param $cfg - глобальные настройки
 * @return array|string - Ответ платформе
 */
function check_session($session_id, $currency, $cfg)
{
    $link = db_connect($cfg);
    $err_msg = "unhandled error in check_session";

    // запросим данные по сессии
    $query = "SELECT user_id, currency, platform_game_id, denomination,status FROM game_sessions WHERE `id` = '" . $session_id . "'";
    $res = mysqli_query($link, $query) or die(mysqli_error($link));
    if ($row_session = mysqli_fetch_array($res)) {
        // если сессия закрыта
        if ($row_session["status"] == "NEW") {
            // запросим данные по игроку
            $query = "SELECT currency, balance, current_session FROM users WHERE `id` = " . $row_session["user_id"];
            $res = mysqli_query($link, $query) or die(mysqli_error($link));
            if ($row_user = mysqli_fetch_array($res)) {
                if ($row_session["currency"] == $row_user["currency"]) {
                    if ($row_session["currency"] == $currency) {
                        $query = "UPDATE `game_sessions` SET status = 'CHECKED' WHERE id = '" . $session_id . "'";
                        if (mysqli_query($link, $query) && mysqli_affected_rows($link) == 1) {
                            $response = [];
                            $response["id_player"] = $row_session["user_id"];
                            $response["balance"] = intval($row_user["balance"]);
                            $response["currency"] = $row_session["currency"];
                            $response["game_id"] = intval($row_session["platform_game_id"]);
                            $response["denomination"] = intval($row_session["denomination"]);
                            return $response;
                        } else {
                            $err_msg = "session status update error";
                        }
                    } else {
                        $err_msg = "wrong currency";
                    }
                } else {
                    $err_msg = "user currency not match session currency";
                }
            } else {
                $err_msg = "user not found";
            }
        } else {
            $err_msg = "wrong session state, must be NEW";
        }
    } else {
        $err_msg = "session " . $session_id . " not found";
    }
    return $err_msg;
}

/**
 * Проверка баланса сессии
 * @param $session_id - идентификатор сессии
 * @param $currency - 3-Latin код валюты
 * @param $cfg - глобальные настройки
 * @return array|string
 */
function check_balance($session_id, $currency, $cfg)
{
    $link = db_connect($cfg);
    $err_msg = "unhandled error in check_balance";

    // запросим данные по сессии
    $query = "SELECT user_id, balance, currency, status FROM game_sessions WHERE `id` = '" . $session_id . "'";
    $res = mysqli_query($link, $query) or die(mysqli_error($link));
    if ($row_session = mysqli_fetch_array($res)) {
        if ($row_session["currency"] == $currency) {
            $response = [];
            $response["currency"] = $row_session["currency"];
            switch ($row_session["status"]) {
                case "NEW":
                case "CHECKED":
                    // запросим данные по пользователю
                    $query = "SELECT balance FROM users WHERE `id` = " . $row_session["user_id"];
                    $res = mysqli_query($link, $query) or die(mysqli_error($link));
                    if ($row_user = mysqli_fetch_array($res)) {
                        $response["balance"] = intval($row_user["balance"]);
                        return $response;
                    } else {
                        $err_msg = "user not found";
                    }
                    break;
                case "ACTIVE":
                    $response["balance"] = intval($row_session["balance"]);
                    return $response;
                case "CLOSED":
                    $err_msg = "session is closed";
                    break;
                case "ERROR":
                    $err_msg = "session has error";
                    break;
                default:
                    $err_msg = "unknown session state";
            }
        } else {
            $err_msg = "wrong currency";
        }
    } else {
        $err_msg = "session " . $session_id . " not found";
    }
    return $err_msg;
}

function process_trx($trx_id, $type, $flag, $delta_amount, $currency, $session_id, $cfg)
{
    $err_msg = "unhandled error in process_trx";

    // Эмуляция случайной ошибки
    /*if (rand(0, 99) < 10) {
        return "random error in " . $type;
    }*/

    $link = db_connect($cfg);
    // игрок с этой сессией еще в сети?
    $query = "SELECT id FROM users WHERE sid IS NOT NULL AND `current_session` = '" . $session_id . "'";
    $res = mysqli_query($link, $query) or die(mysqli_error($link));
    if (mysqli_fetch_array($res)) {

        if (is_numeric($delta_amount)) {
            $query = "INSERT INTO `transactions` (id, type, flag, amount, session) VALUES ('"
                . clean_string($trx_id) . "','" . clean_string($type) . "','" . clean_string($flag) . "'," . $delta_amount . ",'" . clean_string($session_id) . "')";

            if (mysqli_query($link, $query)) {
                $query = "";
                if ($delta_amount <> 0) {
                    $query = "UPDATE `game_sessions` SET balance = balance + (" . $delta_amount . ") WHERE currency = '" . clean_string($currency) .
                        "' AND status = 'ACTIVE' AND id = '" . $session_id . "'";
                }
                if ($delta_amount == 0 || (mysqli_query($link, $query) && mysqli_affected_rows($link) == 1)) {
                    // запросим данные по сессии
                    $query = "SELECT balance, currency FROM game_sessions WHERE `id` = '" . $session_id . "'";
                    $res = mysqli_query($link, $query) or die(mysqli_error($link));
                    if ($row_session = mysqli_fetch_array($res)) {
                        $response = [];
                        $response["currency"] = $row_session["currency"];
                        $response["balance"] = intval($row_session["balance"]);
                        return $response;
                    } else {
                        $err_msg = "session not found";
                    }
                } else {
                    $err_msg = "session amount update error";
                }
            } else {
                $err_msg = "cannot insert transaction in database";
            }
        } else {
            $err_msg = "amount is not a number";
        }
    } else {
        $err_msg = "user had logout";
    }
    return $err_msg;
}

function user_login($id, $cfg)
{
    // создадим и запишем уникальный идентификатор для сессии юзера
    $sid = md5(uniqid(mt_rand(), true));
    $link = db_connect($cfg);
    $query = "UPDATE `users` SET sid = '" . $sid . "' WHERE id = " . $id;
    mysqli_query($link, $query) or die(mysqli_error($link));
    if (mysqli_affected_rows($link) == 1) {
        $_SESSION['id'] = $id;
        $_SESSION['sid'] = $sid;
    }
}

function user_logout($cfg)
{
    if (isset($_SESSION['id']) && is_numeric($_SESSION['id'])) {
        // уберем идентификатор зарегистрированной сессии пользователя
        $link = db_connect($cfg);
        $query = "UPDATE `users` SET sid = NULL WHERE id = " . $_SESSION['id'];
        mysqli_query($link, $query) or die(mysqli_error($link));
    }
    if (isset($_SESSION["gameUrl"]) && $_SESSION["gameUrl"]) {
        // закроем игровую сессию
        close_current_session($_SESSION['id'], $cfg);
    }
    // зачистим переменные сессии
    $_SESSION = [];
}