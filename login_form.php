<?php
session_start();
ob_start();
include_once "config.php";
include_once "db.php";
include_once "utils.php";

if (isset($_SESSION["id"])) {
    $update = update_user($_SESSION["id"], $cfg);
    if ($update) {
        $_SESSION = $update;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sign In</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="./css/main.css">
    <link rel="stylesheet" type="text/css" href="./css/login_form.css">
</head>
<body class="text-center">
<form class="form-signin" method="post">
    <div class="logo">&nbsp;</div>
    <h1 class="h3 mb-3 mt-3 font-weight-normal">Please sign in</h1>
    <?php
    // если указан пользователь
    if (isset($_POST["login"])) {
        if (is_only_valid_symbols($_POST["login"])) {
            // и указан пароль
            if (isset($_POST["password"])) {
                if (strlen($_POST["login"]) <= 32) {
                    if (strlen($_POST["password"]) <= 32) {
                        // подключимся к базе данных
                        include_once "config.php";
                        include_once "db.php";
                        $link = db_connect($cfg);

                        // найдем пользователя с таким паролем
                        $query = "SELECT id FROM users WHERE `login` = '" . clean_string($_POST["login"])
                            . "' AND `password` = '" . clean_string($_POST["password"]) . "' LIMIT 1";
                        $res = mysqli_query($link, $query) or die(mysqli_error($link));
                        if ($row = mysqli_fetch_array($res)) {
                            // если пользователь с заданным паролем найден

                            // если мы заходим в систему - закроем активную игровую сессию,
                            // чтобы избежать списаний в другом браузере и/или на другом устройстве
                            close_current_session($row['id'], $cfg);

                            user_login($row['id'], $cfg);

                            // перйдем на главную страницу системы
                            header("Location: index.php");
                        } else {
                            // такой пользователь есть?
                            $query = "SELECT id FROM users WHERE `login` = '" . clean_string($_POST["login"]) . "' LIMIT 1";
                            $res = mysqli_query($link, $query) or die(mysqli_error($link));
                            if (!mysqli_fetch_array($res)) {
                                // некорректное имя пользователя
                                echo "<span class='alert-danger p-1 mb-1 rounded d-block'>User not found</span>";
                            } else {
                                // некорректный пароль
                                echo "<span class='alert-danger p-1 mb-1 rounded d-block'>Invalid password</span>";
                            }
                        }
                    } else {
                        // слишком длинный пароль
                        echo "<span class='alert-danger p-1 mb-1 rounded d-block'>Password is too long</span><br>";
                    }
                } else {
                    // слишком длинное имя пользователя
                    echo "<span class='alert-danger p-1 mb-1 rounded d-block'>Login is too long</span><br>";
                }
            } else {
                // скажем пользователю, что пароль обязателен
                echo "<span class='alert-danger p-1 mb-1 rounded d-block'>Password is required</span><br>";
            }
        } else {
            // некорректные символы в логине
            echo "<span class='alert-danger p-1 mb-1 rounded d-block'>Invalid symbols in login</span>";
        }
    }
    ?>
    <label for="inputLogin" class="sr-only">Login</label>
    <input type="text" id="inputLogin" name="login" class="form-control" placeholder="Login" required=""
           autofocus=""
           value="<?php if (isset($_POST["login"]) && is_only_valid_symbols($_POST["login"])) echo $_POST["login"] ?>"
           maxlength="32">
    <label for="inputPassword" class="sr-only">Password</label>
    <input type="password" id="inputPassword" name="password" class="form-control" placeholder="Password"
           required="" maxlength="32">
    <button class="btn btn-lg btn-primary btn-block mb-3" type="submit">Sign in</button>
    <span>Don't have an account yet? <a href="/sign_up_form.php">Sign Up!</a></span>
</form>
<script src="https://code.jquery.com/jquery-3.4.1.slim.min.js"
        integrity="sha384-J6qa4849blE2+poT4WnyKhv5vZF5SrPo0iEjwBvKU7imGFAV0wwj1yYfoRSJoZ+n"
        crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"
        integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo"
        crossorigin="anonymous"></script>
<script type="application/javascript" src="bootstrap/js/bootstrap.min.js"></script>
</body>
</html>


