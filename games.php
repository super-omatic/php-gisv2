<?php
include_once "gis_api.php";

/**
 * Преобразует имена групп в человекочитаемые
 * "group_name" => "Group Name"
 * @param $name
 * @return string
 */
function make_group_name($name)
{
    return ucwords(str_replace("_", " ", $name));
}

function show_games_list($cfg)
{
    // запрос игр через GIS API
    $jsonArray = gis_games($cfg);
    if (is_array($jsonArray)) {

        // фильтры для показа игр
        $groups = [];
        // составим массив из всех групп
        foreach ($jsonArray["response"] as $key => $value) {
            if (is_string($value["group"])) array_push($groups, $value["group"]);
        }
        // отфильтруем неуникальные значения
        $groups = array_unique($groups, SORT_LOCALE_STRING);
        if (count($groups) > 0) {
            // покажем модуль фильтрации
            ?>
            <script type="application/javascript">
                function filter_group(group_name) {
                    let games = document.getElementById("games_list").getElementsByClassName("game_wrap");
                    for (let i = 0; i < games.length; i++) {
                        if (group_name === undefined || games[i].dataset.group === group_name) {
                            games[i].classList.remove("d-none");
                        } else {
                            games[i].classList.add("d-none");
                        }
                    }
                    let btns = document.getElementById("filter_panel").getElementsByClassName("btn");
                    for (let i = 0; i < btns.length; i++) {
                        if ((group_name === undefined && btns[i].dataset.group === "All") ||
                            btns[i].dataset.group === group_name) {
                            btns[i].classList.remove("btn-outline-info");
                            btns[i].classList.add("btn-info");
                        } else {
                            btns[i].classList.remove("btn-info");
                            btns[i].classList.add("btn-outline-info");
                        }
                    }
                }
            </script>
            <div id="filter_panel" class="container">
                <a href="#" data-group="All" onclick="filter_group()" class="mb-1 btn btn-info">All</a>
                <?php
                foreach ($groups as $value) {
                    ?>
                    <a href="#" data-group="<?php echo $value ?>" onclick="filter_group('<?php echo $value ?>')"
                       class="mb-1 btn btn-outline-info"><?php echo make_group_name($value) ?></a>
                    <?php
                }
                ?></div>
            <?php
        } else {
            echo '<div class="alert alert-danger">Can\'t make groups list</div>';
        }

        $counter = 0;
        // пройдем по всему массиву игр и покажем их
        foreach ($jsonArray["response"] as $key => $value) {
            $counter++;
            ?>
            <div class="game_wrap" data-group="<?php echo $value["group"] ?>">
                <div class="game_image_wrap">
                    <img loading="lazy" width=240 height=120
                         src="<?php echo $cfg["iconsUrl"] . $value["icon"] ?>"
                         alt="<?php echo $value["title"] ?>" class="game_image">
                </div>
                <div class="game_content">
                    <div class="game_title_wrap"><?php echo $value["title"] ?><br>
                        <span class="group"><?php echo make_group_name($value["group"]) ?></span></div>
                    <div class="game_play">
                        <a <?php
                        if (isset($_SESSION['login'])) {
                            // если пользователь залогинился, то покажем ссылки на игры
                            echo 'href="#" onclick="showGame(' . $value["id"] . '); return false;"';
                        } else {
                            // если пользователь не вошел в систему,
                            //то при клике на игру отправляем залогиниться
                            echo 'href="/login_form.php"';
                        }
                        ?>><i class="fas fa-play-circle"></i></a>
                    </div>
                    <a class="game_play_demo btn btn-warning" href="#"
                       onclick="showGame(<?php echo $value["id"] ?>, true); return false;">
                        Demo
                    </a>
                </div>
            </div>

            <?php
        }
        echo '<div class="counter">total count: ' . $counter . '</div>';
    } else {
        echo '<div class="alert alert-danger">Can\'t receive games list</div>';
    }
}
