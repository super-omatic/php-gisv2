<?php
session_start();
ob_start();
include_once "config.php";
include_once "gis_api.php";
include_once "db.php";
include_once "utils.php";

$currencies = null;
$jsonArray = gis_currencies($cfg);
if (is_array($jsonArray)) {
    $currencies = $jsonArray["response"];
}

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
    <title>Sign Up</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="./css/main.css">
    <link rel="stylesheet" type="text/css" href="./css/login_form.css">
</head>
<body class="text-center">
<form class="form-signin" method="post">
    <div class="logo">&nbsp;</div>
    <h1 class="h3 mb-3 mt-3 font-weight-normal">Registration</h1>
    <?php
    if (!is_array($currencies)) {
        echo "<span class='alert-danger p-1 mb-1 rounded d-block'>Can't get currency list from Platform</span>";
    }
    // если указан пользователь
    if (isset($_POST["login"])) {
        // указан пароль
        if (isset($_POST["password"])) {
            // указана валюта
            if (isset($_POST["currency"])) {
                // проверим, что валюта из предлагаемого списка
                $isCurrencyValid = false;
                if (is_array($currencies)) {
                    foreach ($currencies as $key => $value) {
                        if ($value["code"] == $_POST["currency"]) {
                            $isCurrencyValid = true;
                            break;
                        }
                    }
                }
                if ($isCurrencyValid) {
                    if (is_only_valid_symbols($_POST["login"])) {
                        if (is_only_valid_symbols($_POST["password"])) {
                            if (strlen($_POST["login"]) <= 32) {
                                if (strlen($_POST["password"]) <= 32) {
                                    if (strlen($_POST["login"]) > 3) {
                                        // подключимся к базе данных
                                        include_once "config.php";
                                        include_once "db.php";
                                        $link = db_connect($cfg);

                                        // такой пользователь есть?
                                        $query = "SELECT id FROM users WHERE `login` = '" . clean_string($_POST["login"]) . "' LIMIT 1";
                                        $res = mysqli_query($link, $query) or die(mysqli_error($link));
                                        if (mysqli_fetch_array($res)) {
                                            // такой пользователь уже есть
                                            echo "<span class='alert-danger p-1 mb-1 rounded d-block'>User already exists</span>";
                                        } else {
                                            $id = add_user($_POST["login"], $_POST["password"], $_POST["currency"], $cfg);

                                            if ($id) {
                                                user_login($id, $cfg);
                                                header("Location: index.php");
                                            } else {
                                                // ошибка при добавлении пользователя
                                                echo "<span class='alert-danger p-1 mb-1 rounded d-block'>User add error</span><br>";
                                            }
                                        }
                                    } else {
                                        // слишком короткое имя пользователя
                                        echo "<span class='alert-danger p-1 mb-1 rounded d-block'>Login must contain at least 4 symbols</span><br>";
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
                            // некорректные символы в пароле
                            echo "<span class='alert-danger p-1 mb-1 rounded d-block'>Invalid symbols in password</span><br>";
                        }
                    } else {
                        // некорректные символы в логине
                        echo "<span class='alert-danger p-1 mb-1 rounded d-block'>Invalid symbols in login</span><br>";
                    }
                } else {
                    // некорректная валюта
                    echo "<span class='alert-danger p-1 mb-1 rounded d-block'>Please, choose another currency</span><br>";
                }
            } else {
                // валюту нужно выбрать
                echo "<span class='alert-danger p-1 mb-1 rounded d-block'>Currency is required</span><br>";
            }
        } else {
            // пароль обязателен
            echo "<span class='alert-danger p-1 mb-1 rounded d-block'>Password is required</span><br>";
        }
    }
    ?>
    <label for="inputLogin" class="sr-only">Login</label>
    <input type="text" id="inputLogin" name="login" class="form-control" placeholder="Login" required=""
           autofocus="" value="<?php if (isset($_POST["login"])) echo $_POST["login"] ?>" maxlength="32">
    <label for="inputPassword" class="sr-only">Password</label>
    <input type="password" id="inputPassword" name="password" class="form-control" placeholder="Password"
           required="" maxlength="32">

    <label for="currency"">Currency</label>
    <select class="custom-select d-block w-100" name="currency" id="currency" required="">
        <option value="">Choose...</option>
        <?php
        if (is_array($currencies)) {
            foreach ($currencies as $key => $value) {
                echo "<option value='" . $value["code"] . "'>" . $value["title"] . " (" . $value["code"] . ")</option>";
            }
        }
        ?>
    </select>

    <div class="custom-control custom-checkbox mt-3">
        <input type="checkbox" class="custom-control-input" id="terms" value="1" required="">
        <label class="custom-control-label" for="terms">I accept <a href="/terms.php">Terms and Conditions</a></label>
    </div>

    <button class="btn btn-lg btn-primary btn-block mt-2 mb-3" type="submit">Sign up</button>
    <span>Already Registered User?<br/><a href="/login_form.php">Click here to login!</a></span>
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


