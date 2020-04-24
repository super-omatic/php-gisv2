<?php
$cfg = [];

// Параметры Партнера
$cfg["mainTitle"] = "PHP API 2.0 Demo Integration"; // Заголовок в шапке сайта
$cfg["startBalance"] = 100000; // баланс при регистрации
$cfg["denomination"] = 100; // деноминатор партнера в копейках (сотые доли)
$cfg["denominationValues"] = array(1, 5, 10, 25, 50, 100, 200, 500, 1000, 2000); // деноминатор партнера в копейках (сотые доли)
$cfg["demoCurrency"] = "CRD";
$cfg["demoBalance"] = 100000;

// параметры подключения к GIS
$cfg["apiUrl"] = "https://api.superplat.pw./api/gisv2/"; // адрес подключения
$cfg["iconsUrl"] = "https://webgl.greenhost.pw/btns/"; // адресс папки с иконками
$cfg["partner.alias"] = "partneralias"; // Ваш логин в системе (можно говорить поддержке)
$cfg["secretKey"] = "secretkey"; // (никому не сообщать!) может быть получен и изменен Партнером самостоятельно в любое время в Личном Кабинете

$cfg["gisErrorLog"] = "gisError.log"; // расположение файла с ошибками от GIS

// переменные для соединения с базой данных
$cfg["hostname"] = "127.0.0.1";
$cfg["username"] = "root";
$cfg["password"] = "1";
$cfg["dbName"] = "gis_2";

// выключить все сообщения об ошибках от PHP
// (Рекомендуемый вариант для прода)
//error_reporting(0);

// показывать все ошибки
// (Рекомендуемый вариант для тестирования качества кода)
//error_reporting(E_ALL);

// показывать ошибки. Предупреждения и уведомления скрыть
// (Рекомендуемый вариант для визуальных тестов)
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);