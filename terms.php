<?php
// вариант интергации с игрой в iframe
session_start();
ob_start();

if (file_exists("config.php")) {
    include_once "config.php";
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
}
?>
<!DOCTYPE html>
<html lang="en" class="h-100">
<head>
    <meta charset="UTF-8">
    <title>Integration Example</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="./fontawesome-free-5.13.0-web/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="./css/main.css">
    <script type="application/javascript">
        function showGame(id, isDemo) {
            let xhr = new XMLHttpRequest();
            xhr.withCredentials = true;
            xhr.open("GET", "/game_enter" + (isDemo ? "_demo" : "") + ".php?id=" + id, true);
            xhr.onload = (_event) => {
                if (xhr.status === 200) {
                    document.getElementById('iframeWrapper').innerHTML = xhr.response;
                    document.getElementById('gameWrapper').classList.remove("d-none");
                    document.getElementById('gameWrapper').classList.add("d-flex");
                    document.getElementById('body').classList.add("overflow-hidden");
                }
            };
            xhr.send();
        }

        function closeGame() {
            let xhr = new XMLHttpRequest();
            xhr.withCredentials = true;
            xhr.open("GET", "/close_current_game.php", true);
            xhr.onload = (_event) => {
                if (xhr.status === 200) {
                    document.getElementById('iframeWrapper').innerHTML = "";
                    document.getElementById('gameWrapper').classList.add("d-none");
                    document.getElementById('gameWrapper').classList.remove("d-flex");
                    document.getElementById('body').classList.remove("overflow-hidden");
                }
            };
            xhr.send();
        }
    </script>
</head>
<body id="body" class="d-flex flex-column h-100">
<?php
if (file_exists("config.php")) {
    ?>
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
    <div class="games container-xl text-justify">
        <?php
        db_connect($cfg); // проверим соединение с базой данных
        ?>
        <h1>Terms and Conditions</h1>
        <p>
            Lorem ipsum dolor sit amet, consectetur adipiscing elit. Phasellus elementum, eros id aliquet accumsan,
            lectus dui lobortis neque, ut molestie purus neque ut nunc. Quisque imperdiet imperdiet nisl, id dictum elit
            hendrerit a. Aliquam tellus magna, varius ac accumsan sit amet, mollis eget diam. Class aptent taciti
            sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Curabitur libero nunc, egestas sit
            amet hendrerit non, imperdiet sed nunc. Pellentesque condimentum in metus quis interdum. Maecenas vulputate
            felis quis felis semper, at congue erat interdum. Quisque id dui urna. Duis at ipsum sollicitudin nunc
            pretium condimentum in a neque. Fusce in convallis purus. Cras quis sapien ipsum. Nam semper hendrerit ipsum
            in pellentesque. Quisque auctor ultrices vulputate. Vivamus quis condimentum nisi. Vivamus scelerisque quam
            vel odio porta, nec lobortis mi porta.
        </p>
        <p>
            Quisque tristique at ex ac laoreet. Duis tempus, neque nec iaculis vulputate, turpis mauris fermentum nibh,
            a volutpat nisl ante ornare libero. Etiam nec nisi feugiat, dapibus ex vitae, semper sapien. Fusce malesuada
            ante vitae augue posuere, non mattis diam finibus. Praesent sed felis eros. Quisque tempor efficitur turpis,
            vel vulputate massa cursus id. Morbi sed ex malesuada, molestie nulla sit amet, sagittis nunc.
        </p>
        <p>
            Nullam iaculis metus ac est bibendum, aliquam blandit sapien tincidunt. Nunc a ligula orci. Curabitur nisl
            quam, rhoncus eget mollis ac, tristique porta odio. Aenean eu nisi odio. Vestibulum auctor laoreet justo, ut
            semper eros pretium quis. Etiam posuere leo quis nisi gravida feugiat. Nam porttitor consequat dolor, sit
            amet iaculis tortor semper ac.
        </p>
        <p>
            Donec mauris ipsum, rutrum quis urna quis, commodo venenatis dolor. Nullam egestas aliquet vestibulum.
            Aenean a felis id sem dignissim cursus. Aenean a eros urna. Nulla vitae nisl vitae nisi pellentesque
            sodales. Nulla facilisi. Nam non leo in nibh feugiat luctus. Nam quis risus et metus mattis laoreet nec a
            lacus. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Morbi non
            sem feugiat, mattis purus ut, rhoncus dolor. Donec sed elementum elit, eget euismod augue. Vestibulum ante
            ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Nunc ornare interdum volutpat.
            Mauris fermentum facilisis felis, eget ultrices erat pharetra vitae. Donec scelerisque nisl id felis
            lacinia, ac vestibulum massa congue. Nam at rutrum tellus.
        </p>
        <p>
            Aliquam erat volutpat. Maecenas sed nisl eu purus suscipit dignissim a sed enim. Ut sollicitudin dui ut
            ullamcorper malesuada. Nullam in blandit nisi. Sed nulla ex, efficitur ac viverra et, convallis eu eros.
            Proin ac lacus neque. Fusce vel mi eget dui venenatis auctor a a enim. Ut ultricies dui vitae dapibus
            interdum. Vestibulum sed consectetur arcu. Sed sed libero eu justo bibendum maximus molestie et mi.
        </p>
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