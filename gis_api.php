<?php
/**
 * Формирование запроса к GIS
 * @param $action - оконечный url (Endpoint URL)
 * @param $request_method - POST или GET
 * @param $requestParams -  массив параметров запроса
 * @param $cfg - глобальные настройки
 * @return false|string
 */
function gis_request($action, $request_method, $requestParams, $cfg)
{
    $contentType = "application/json";
    $content = json_encode($requestParams);

    $url = $cfg['apiUrl'] . $action;

    $options = array(
        'http' => array(
            'header' => "Content-type: " . $contentType . "\r\n",
            'method' => $request_method,
            'content' => $content,
            'ignore_errors' => true
        )
    );

    return file_get_contents($url, false, stream_context_create($options));
}

/**
 * Компаратор для фильтрации параметров запроса
 * @param $param - имя проверяемого параметра
 * @return bool - true если параметр подходит
 */
function gis_filter_for_sign($param)
{
    return
        !( // уберем параметры
            (strpos($param, 'partner.') === 0) || // начинающиеся с "partner."
            $param === "meta" || // объект meta
            $param === "sign" // саму подпись
        );
}

/**
 * Проверка подписи
 * @param $requestParams - параметры полученные в запросе
 * @param $method - метод который обратился за подписью
 * @param $cfg - глобальный настройки
 * @return string - подпись
 */
function make_sign($requestParams, $method, $cfg)
{
    // получим только параметры необходимые для вычисления подписи
    // для версий PHP >=5.6
    // $params = array_filter($requestParams, "gis_filter_for_sign", ARRAY_FILTER_USE_KEY);

    // вариант фильтрации для версий PHP < 5.6
    $params = $requestParams;
    if (isset($params["sign"])) unset($params["sign"]);
    if (isset($params["meta"])) unset($params["meta"]);
    foreach ($params as $key => $value) {
        if (strpos($key, 'partner.') === 0) unset($params[$key]);
    }

    // упорядочим их лексикографически
    ksort($params, SORT_NATURAL);

    // объединим все параметры в строку
    $joined = "";
    foreach ($params as $key => $value) {
        $joined .= "&" . $key . "=" . $value;
    }
    $joined = substr($joined, 1);
    // Соединительная частица "&" между 'joined' и 'serviceName' проставляется в любом случае, даже при отсутствии параметров.
    $signString = $joined . "&" . $method . "&" . $cfg["partner.alias"] . "&" . $cfg["secretKey"];

    // получим md5 хеш
    return md5($signString);
}

/**
 * Проверка подписи
 * @param $requestParams - параметры полученные в запросе
 * @param $method - метод который обратился за подписью
 * @param $cfg - глобальный настройки
 * @return bool
 */
function gis_check_sign($requestParams, $method, $cfg)
{
    // проверяем подпись
    // получим подпись запроса
    $sign = null;
    if (isset($requestParams["sign"])) {
        $sign = $requestParams["sign"];
    } else {
        // подпись не найдена
        return false;
    }
    if ($sign == null || !is_string($sign) || empty($sign)) {
        // подпись не строка
        return false;
    }

    $md5 = make_sign($requestParams, $method, $cfg);

    // проверяем подпись без учета регистра
    if (strcasecmp($md5, $sign) <> 0) {
        //gis_write_error($signString, $cfg);
        // подпись не совпала
        return false;
    }
    return true;
}

/**
 * Инициализация/начало игровой сессии GIS
 * @param $requestParams - массив параметров запроса
 * @param $cfg - глобальные настройки
 * @return array - массив с ответом GIS
 */
function gis_init($requestParams, $cfg)
{
    // получение ответа в виде JSON строки
    $result = gis_request("init.session", "POST", $requestParams, $cfg);

    // проверим правильность ответа и преобразуем его в массив
    return gis_process_response($result, $cfg);
}

/**
 * Принудительное закрытие сессии на Платформе
 * @param $requestParams
 * @param $cfg
 * @return array|false
 */
function gis_close_session($requestParams, $cfg){
    // получение ответа в виде JSON строки
    $result = gis_request("close.session", "POST", $requestParams, $cfg);

    // проверим правильность ответа и преобразуем его в массив
    return gis_process_response($result, $cfg);
}

/**
 * Получение информации по фрираундам
 * @param $requestParams
 * @param $cfg
 * @return array|false
 */
function gis_freerounds_info($requestParams, $cfg){
    // получение ответа в виде JSON строки
    $result = gis_request("games.freeroundsInfo", "POST", $requestParams, $cfg);

    // проверим правильность ответа и преобразуем его в массив
    return gis_process_response($result, $cfg);
}

/**
 * Инициализация/начало ДЕМО игровой сессии GIS
 * @param $requestParams - массив параметров запроса
 * @param $cfg - глобальные настройки
 * @return array - массив с ответом GIS
 */
function gis_init_demo($requestParams, $cfg)
{
    // получение ответа в виде JSON строки
    $result = gis_request("init.demo.session", "POST", $requestParams, $cfg);

    // проверим правильность ответа и преобразуем его в массив
    return gis_process_response($result, $cfg);
}

/**
 * Получение списка игр дотупных для мерчанта в GIS
 * @param $cfg - глобальные настройки
 * @return array - массив с ответом GIS
 */
function gis_games($cfg)
{
    // получение ответа в виде JSON строки
    $result = gis_request("games.list", "GET", [], $cfg);

    // проверим правильность ответа и преобразуем его в массив
    return gis_process_response($result, $cfg);
}

/**
 * Получение списка дотупных валют
 * @param $cfg - глобальные настройки
 * @return array - массив с ответом GIS
 */
function gis_currencies($cfg)
{
    // получение ответа в виде JSON строки
    $result = gis_request("currencies.list", "GET", [], $cfg);

    // проверим правильность ответа и преобразуем его в массив
    return gis_process_response($result, $cfg);
}

/**
 * Проверим ответ GIS на ошибки
 * @param $response - ответ GIS
 * @param $cfg - глобальные настройки
 * @return array|false - массив с ответом GIS
 */
function gis_process_response($response, $cfg)
{
    if ($response) {
        // превратим JSON строку в массив
        $result = json_decode($response, true);
        // проверим статус на ошибки
        if (isset($result["status"]) && $result["status"] <> 200) {
            gis_write_error($response, $cfg);
        }
        // вернем ответ от GIS, даже если он с ошибкой
        return $result;
    } else {
        gis_write_error("no response from platform", $cfg);
        return false;
    }
}

/**
 * Записать ошибку в лог
 * @param $errMessage - сообщение об ошибке
 * @param $cfg - глобальные настройки
 */
function gis_write_error($errMessage, $cfg)
{
    $fp = fopen($cfg["gisErrorLog"], 'a+');
    fwrite($fp, $errMessage . "\r\n");
    fclose($fp);
}