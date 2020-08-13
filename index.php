<?php
// вариант интергации с игрой в iframe
session_start();
ob_start();

if (file_exists("config.php")) {
    include_once "config.php";
    include_once "utils.php";
    include_once "gis_api.php";
    include_once "db.php";
    include_once "games.php";

    if (isset($_SESSION["id"])) {
        $update = update_user($_SESSION["id"], $cfg);
        if ($update) {
            $_SESSION = $update;
        } else {
            user_logout($cfg);
        }
    }

    // будем ли грузить игру из сессиии?
    $isGameUrl = isset($_SESSION["gameUrl"]) && $_SESSION["gameUrl"];
}
?>
<!DOCTYPE html>
<html lang="en" class="h-100">
<head>
    <meta charset="UTF-8">
    <title>Integration Example<?php if (isset($_SESSION["login"])) echo " | " . $_SESSION["login"] ?></title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="./fontawesome-free-5.13.0-web/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="./css/main.css">
    <script type="application/javascript">
        let isInGame = <?php if ($isGameUrl) {
            echo "true";
        } else {
            echo "false";
        }; ?>;

        function showGame(id, isDemo) {
            if (isInGame) return;
            isInGame = true;

            let xhr = new XMLHttpRequest();
            xhr.withCredentials = true;
            xhr.open("GET", "/game_enter" + (isDemo ? "_demo" : "") + ".php?id=" + id, true);
            xhr.onload = (_event) => {
                if (xhr.status === 200) {
                    try {
                        let response = JSON.parse(xhr.response);
                        document.getElementById('iframeWrapper').innerHTML = response["iframe"];
                        document.getElementById('gameToken').innerHTML = response["token"];
                    } catch (e) {
                        document.getElementById('iframeWrapper').innerHTML = xhr.response;
                    }
                    document.getElementById('gameWrapper').classList.remove("d-none");
                    document.getElementById('gameWrapper').classList.add("d-flex");
                    document.getElementById('body').classList.add("overflow-hidden");
                    let lbDemo = document.getElementById('demoMode');
                    let btnAddCredits = document.getElementById('addCredits');

                    let buttonExit = document.getElementById('buttonExit');
                    if (buttonExit) {
                        buttonExit.dataset.isDemo = isDemo;
                    }

                    let buttonExitCold = document.getElementById('buttonExitCold');
                    let buttonExitHot = document.getElementById('buttonExitHot');
                    if (buttonExitCold) {
                        if (isDemo) {
                            buttonExitCold.classList.add("d-none");
                            buttonExitHot.classList.add("d-none");
                            buttonExit.classList.remove("d-none");
                        } else {
                            buttonExitCold.classList.remove("d-none");
                            buttonExitHot.classList.remove("d-none");
                            buttonExit.classList.add("d-none");
                        }
                    }

                    let lbFreeRounds = document.getElementById('lb-freerounds');
                    if (lbFreeRounds) {
                        lbFreeRounds.innerHTML = "";
                    }
                    if (isDemo) {
                        lbDemo.classList.add("d-inline-block");
                        lbDemo.classList.remove("d-none");
                        btnAddCredits.classList.add("d-none");
                    } else {
                        btnAddCredits.classList.remove("d-none");
                        lbDemo.classList.remove("d-inline-block");
                        lbDemo.classList.add("d-none");
                        if (lbFreeRounds) {
                            updateFreeRoundsWidget(lbFreeRounds);
                        }
                    }
                } else {
                    isInGame = false;
                }
            };
            xhr.send();
        }

        function updateFreeRoundsWidget(lbFreeRounds) {
            let xhr = new XMLHttpRequest();
            xhr.withCredentials = true;
            xhr.open("GET", "/update_freerounds_widget.php", true);
            xhr.onload = (_event) => {
                if (!isInGame) return;
                if (xhr.status === 200) {
                    try {
                        let response = JSON.parse(xhr.response);
                        if (response.success && ![0, "0"].includes(response.total)) {
                            <?php
                            if($cfg["isProduction"]){
                            ?>
                            lbFreeRounds.innerHTML = "<b>Free Rounds: " + (parseInt(response.total) - parseInt(response.step)) + "</b>";
                            <?php
                            }else{
                            ?>
                            lbFreeRounds.innerHTML = "<b>Free Rounds " + response.step + " of " + response.total + "</b>";
                            <?php
                            }
                            ?>
                            lbFreeRounds.classList.add("d-inline-block");
                            lbFreeRounds.classList.remove("d-none");
                            setTimeout(() => {
                                updateFreeRoundsWidget(lbFreeRounds)
                            }, 1000);
                            return;
                        }
                    } catch (e) {
                        console.error(e);
                    }
                }
                lbFreeRounds.innerHTML = "";
                lbFreeRounds.classList.remove("d-inline-block");
                lbFreeRounds.classList.add("d-none");
            };
            xhr.send();
        }

        function updateUnfinishedFreeRoundsWidget() {
            let xhr = new XMLHttpRequest();
            xhr.withCredentials = true;
            xhr.open("GET", "/unfinished_freerounds_widget.php", true);
            xhr.onload = (_event) => {
                if (xhr.status === 200) {
                        document.getElementById('freerounds_list').innerHTML = xhr.response;
                }
            };
            xhr.send();
        }

        function doubleFormatter(amount, minor_units) {
            amount = amount + "";
            while (amount.length <= minor_units) {
                amount = "0" + amount;
            }
            return amount.substr(0, amount.length - minor_units) + "." + amount.substr(-minor_units);
        }

        function addCredits() {
            console.log("+100");
            let xhr = new XMLHttpRequest();
            xhr.withCredentials = true;
            xhr.open("GET", "/add_amount.php", true);
            xhr.onload = (_event) => {
                if (!isInGame && xhr.status === 200) {
                    let response = JSON.parse(xhr.response);
                    if (response.success) {
                        updateBalance(response.login, response.balance, response.currency, response.denomination)
                    }
                }
            };
            xhr.send();
        }

        function addFreeRounds(count) {
            if (!isInGame) {
                let xhr = new XMLHttpRequest();
                xhr.withCredentials = true;
                xhr.open("POST", "/add_freerounds.php", true);
                xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                xhr.onload = (_event) => {
                    if (xhr.status === 200) {
                        let response = JSON.parse(xhr.response);
                        let fr = response.success ? response.freerounds : 0;
                        let lbFRCount = document.getElementById("lb-fr-count");
                        if (lbFRCount) {
                            lbFRCount.style.display = [0, "0"].includes(fr) ? "none" : "block";
                        }
                        updateFreeRounds(fr);
                    }
                };
                xhr.send("count=" + count);
            }
        }

        function toggleDebug(count) {
            let xhr = new XMLHttpRequest();
            xhr.withCredentials = true;
            xhr.open("POST", "/toggledebugmode.php", true);
            xhr.onload = (_event) => {
                if (xhr.status === 200) {
                    let response = JSON.parse(xhr.response);
                    let lb = "err";
                    if (response.success) {
                        lb = response.debug ? "on" : "off";
                    } else {
                        console.error(response.error);
                    }
                    document.getElementById("lb-debug").innerHTML = lb;
                    let inGameLb = document.getElementById("lb-debug-game");
                    if (inGameLb) inGameLb.innerHTML = lb;
                }
            };
            xhr.send("count=" + count);
        }

        function updateFreeRounds(freerounds) {
            // количество фрираундов - число
            if (Number.isInteger(freerounds)) {
                document.getElementById("fr-count").innerHTML = freerounds;
            } else {
                // что-то пошло не так. Обновим страницу за свежими данными
                window.location.reload();
                return false;
            }
        }

        function updateBalance(login, balance, currency, denomination) {
            // что-то пошло не так. Обновим страницу за свежими данными
            if (login === undefined) {
                window.location.reload();
                return false;
            }

            if (document.getElementById('userBalance')) {
                // проставим ответ в инпут
                document.getElementById("denominationInput").value = doubleFormatter(denomination, 2);
                // убедимся в корректности деноминатора
                if (Number.isInteger(denomination)) {
                    // включим/выклоючим кнопки изменения деноминации
                    let index = window.denominations.indexOf(denomination, 10);
                    let btnPlus = document.getElementById("denominationPlus");
                    let btnMinus = document.getElementById("denominationMinus");
                    if (index === 0) {
                        btnMinus.setAttribute("disabled", "disabled");
                    } else {
                        btnMinus.removeAttribute("disabled");
                    }
                    if (index === window.denominations.length - 1) {
                        btnPlus.setAttribute("disabled", "disabled");
                    } else {
                        btnPlus.removeAttribute("disabled");
                    }
                    // обновим логин пользователя,
                    // для гарантиии, что мы показываем баланс именно того пользователя, что нужно
                    document.getElementById("userCreditBalance").innerHTML = "User: " + login;
                    // обновим баланс в кредитах
                    document.getElementById("userCreditBalance").innerHTML = "Balance: " +
                        doubleFormatter(Math.floor(balance * 100 / denomination), 2);
                    // обновим основной баланс
                    document.getElementById("userBalance").innerHTML = "(" +
                        doubleFormatter(balance, 2) + " " + currency + ")";

                } else {
                    document.getElementById("userCreditBalance").innerHTML = "Credit: error"
                }
            }
            return true;
        }

        function closeGame(hotClose = false) {
            let xhr = new XMLHttpRequest();
            xhr.withCredentials = true;
            xhr.open("GET", "/close_current_game.php" + (hotClose ? "?hc=1" : ""), true);
            xhr.onload = (_event) => {
                if (xhr.status === 200) {
                    let response = JSON.parse(xhr.response);
                    if (response.integrationError) console.error(response.integrationError);
                    if (response.success) {
                        if (updateBalance(response.login, response.balance, response.currency, response.denomination)) {
                            isInGame = false;
                            document.getElementById('iframeWrapper').innerHTML = "";
                            document.getElementById('gameWrapper').classList.add("d-none");
                            document.getElementById('gameWrapper').classList.remove("d-flex");
                            document.getElementById('body').classList.remove("overflow-hidden");
                            addFreeRounds(0);
                            updateUnfinishedFreeRoundsWidget();
                        }
                    } else {
                        document.getElementById('iframeWrapper').innerHTML = "<div class='alert alert-danger'>" + response.error + "</div>";
                        console.error(response.error);
                    }
                }
            };
            xhr.send();
        }

        function initIFrameFocus(iframe) {
            console.warn('init focus');
            iframe.addEventListener('focusout', () => {
                iframe.focus();
                console.log('focus returned')
            });
            iframe.focus();
        }
    </script>
</head>
<body id="body" class="d-flex flex-column h-100 <?php if ($isGameUrl) echo "overflow-hidden" ?>">
<?php
// если при получении пользователя что-то пошло не так
if (isset($_SESSION["err"])) {
    ?>
    <div class="container-md">
        <div class='alert alert-danger'>Error: <?php echo $_SESSION["err"] ?></div>
    </div>
    <?php
}

if (file_exists("config.php")) {
    ?>
    <div id="gameWrapper" class="flex-column h-100 fixed-top bg-dark <?php
    if ($isGameUrl) {
        // покажем блок с игрой используя класс Bootstrap
        echo "d-flex";
    } else {
        // скроем блок
        echo "d-none";
    }
    ?>">
        <div class="d-flex flex-column flex-md-row align-items-center m-0 p-2"><a href="#">
                <div class="logo">&nbsp;</div>
            </a>
            <span class="ml-3 text-light mr-md-auto "><?php echo $cfg["mainTitle"]; ?></span>
            <span class="text-light m-3 d-none" id="lb-freerounds"></span>
            <span class="text-light m-3 d-none" id="demoMode"><b>DEMO MODE</b></span>
            <span class="text-light m-3" id="gameToken"><?php if ($isGameUrl) {
                    echo substr($_SESSION["gameUrl"], strpos($_SESSION["gameUrl"], "?t=") + 3);
                } ?></span>
            <a class="btn btn-outline-primary mr-1" id="addCredits" href="#" onclick="addCredits(); return false;">Add
                100<?php echo $_SESSION["currency"]; ?></a>
            <?php if (!$cfg["isProduction"]) { ?>
                <a class="btn btn-outline-primary mr-1" href="#" onclick="toggleDebug(); return false;">Debug: <span
                            id="lb-debug-game"><?php if (is_debug()) echo "on"; else echo "off" ?></span></a>
            <?php }
            if (!$cfg["isProduction"]) {
                ?>
                <a id="buttonExitHot" class="btn btn-outline-primary mr-1 alert-danger" href="#"
                   onclick="closeGame(true); return false;"><i
                            class="fas fa-times"></i></a>
                <a id="buttonExitCold" class="btn btn-outline-primary mr-1 alert-info" href="#"
                   onclick="closeGame(false); return false;"><i
                            class="fas fa-times"></i></a>
            <?php } ?>
            <a class="btn btn-outline-primary mr-1 <?php if (!$cfg["isProduction"]) echo "d-none"; ?>" id="buttonExit"
               href="#"
               onclick="closeGame(!this.dataset.isDemo); return false;"><i
                        class="fas fa-times"></i></a>
        </div>
        <div id="iframeWrapper" class="d-flex flex-column h-100 flex-row">
            <?php
            // если игра грузится по ссылке из сессии
            if ($isGameUrl) {
                echo '<iframe onload="initIFrameFocus(this)" class="w-100 h-100" src="' . $_SESSION["gameUrl"] . '"></iframe>';
                echo '<script>
                        let lbFreeRounds = document.getElementById("lb-freerounds");
                        if (lbFreeRounds) {
                            lbFreeRounds.innerHTML = "";
                            updateFreeRoundsWidget(lbFreeRounds);
                        }
                       </script>';
            }
            ?>
        </div>
    </div>

    <div class="d-flex flex-column flex-md-row align-items-center p-3 px-md-4 mb-3 bg-white border-bottom shadow-sm">
        <div class="logo">&nbsp;</div>
        <h5 class="ml-3 my-0 mr-md-auto font-weight-normal"><?php echo $cfg["mainTitle"]; ?></h5>
        <?php
        if (isset($_SESSION["login"])) {
            include "header.php";
        } else {
            echo '<a class="btn btn-outline-secondary ml-2" href="sign_up_form.php">Sign up</a>';
            echo '<a class="btn btn-outline-primary ml-2" href="login_form.php">Sign in</a>';
        }
        ?>
    </div>
    <?php
    // начнем показ страницы не дожидаясь получения списка игр
    ob_end_flush();
    ?>

    <!-- неоконченые фрираунды -->
    <div id="freerounds_list" class="games container-xl text-center"></div>
    <script>updateUnfinishedFreeRoundsWidget()</script>

    <!-- Список игр -->
    <div id="games_list" class="games container-xl text-center">
        <?php
        db_connect($cfg); // проверим соединение с базой данных
        show_games_list($cfg);
        ?>
    </div>
    <?php
} else {
    ?>
    <div class="container-md">
        <div class='alert alert-danger'>Error: <b>config.php</b> does not exist.<br>
            You need to rename config.sample.php to config.php
        </div>
    </div>
    <?php
}
?>

<footer class="footer mt-auto py-3 bg-dark">
    <div class="container">
        <span class="text-muted"><?php echo $cfg["mainTitle"]; ?></span>
    </div>
</footer>
<script src="https://code.jquery.com/jquery-3.4.1.slim.min.js"
        integrity="sha384-J6qa4849blE2+poT4WnyKhv5vZF5SrPo0iEjwBvKU7imGFAV0wwj1yYfoRSJoZ+n"
        crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"
        integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo"
        crossorigin="anonymous"></script>
<script type="application/javascript" src="bootstrap/js/bootstrap.min.js"></script>
</body>
</html>

