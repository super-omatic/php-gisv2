<!-- Пользователь и его баланс -->
<span class="p-1 text-center m-2" id="userLogin">User: <?php echo $_SESSION["login"] ?></span>

<!-- Выбор деноминатора -->
<span class="p-1 text-center m-2">Coin value:</span>
<script type="application/javascript">
    window.denominations = JSON.parse("<?php echo json_encode($cfg["denominationValues"])?>");
    console.log("Denominations: " + window.denominations);

    function change_denomination(isHigher) {
        let changeDirection = 0;
        if (isHigher) {
            changeDirection = 1;
        } else {
            changeDirection = -1;
        }
        if (changeDirection !== 0) {
            let xhr = new XMLHttpRequest();
            xhr.withCredentials = true;
            xhr.open("POST", "/change_denomination.php", true);
            xhr.onload = (_event) => {
                if (xhr.status === 200) {
                    let response = JSON.parse(xhr.response);
                    if (updateBalance(response.login, response.balance, response.currency, response.denomination)) {
                        // включим/выклоючим кнопки изменения деноминации
                        let index = window.denominations.indexOf(response.denomination);
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
                    }
                }
            };
            xhr.send("" + changeDirection);
        }
    }
</script>
<div class="input-group w-auto">
    <div class="input-group-prepend">
        <button id="denominationMinus" type="submit" class="btn btn-secondary" onclick="change_denomination(false)">-
        </button>
    </div>
    <input id="denominationInput" size="5" type="text" class="form-control text-center"
           value="<?php echo double_formatter($_SESSION["denomination"], 2) ?>" readonly>
    <div class="input-group-append">
        <button id="denominationPlus" type="submit" class="btn btn-secondary" onclick="change_denomination(true)">+
        </button>
    </div>
</div>

<!-- Баланс пользователя -->
<span class="p-1 text-center ml-2" id="userCreditBalance">Balance: <?php
    echo double_formatter(intval(intval($_SESSION["balance"]) * 100) / intval($_SESSION["denomination"]), 2)
    ?></span><span class="p-1 text-center mr-2" id="userBalance">(<?php
    echo double_formatter($_SESSION["balance"], 2) . " " . $_SESSION["currency"]
    ?>)</span>

<?php
if($cfg["isProduction"]){
    ?>
    <!-- Количество фрираундов -->
    <span id="lb-fr-count">FreeRounds: <span class="mr-1" id="fr-count">0</span></span>
    <?php
}else {
    ?>
    <!-- Добавить кредитов -->
    <a class="btn btn-outline-primary mr-1" href="#" onclick="addCredits(); return false;">Add
        100<?php echo $_SESSION["currency"]; ?></a>

    <!-- Количество фрираундов -->
    <a class="btn btn-outline-primary mr-1" href="#" onclick="addFreeRounds(1); return false;">FR:<span
                id="fr-count">0</span>+</a>

    <!-- Включить/выключить дебаг -->
    <a class="btn btn-outline-primary mr-1" href="#" onclick="toggleDebug(); return false;">Debug: <span
                id="lb-debug"><?php if(is_debug())echo "on"; else echo "off" ?></span></a>
    <?php
}
?>

<script>
    addFreeRounds(0);
</script>

<!-- Выход -->
<a class="btn btn-outline-primary" href="user_logout.php">Logout</a>


