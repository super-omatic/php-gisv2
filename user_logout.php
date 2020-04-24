<?php
session_start();
ob_start();

include_once "config.php";
include_once "db.php";

// выход из игры
user_logout($cfg);

// уничтожим сессии
session_destroy();
// перейдем на главную страницу системы
header("Location: index.php");


