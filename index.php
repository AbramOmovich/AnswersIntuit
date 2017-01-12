    <?php
    include 'inc/eVars.php';
    include 'inc/functions.php';
    ob_start();
?>

    <!doctype html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <title>Решебник Интуит</title>
        <link rel="stylesheet" href="css/bootstrap.min.css">
        <link rel="stylesheet" href="css/bootstrap.css">
    </head>
    <body>
    <div class="container" >
        <div class="col-lg-11" align="center">
            <h2>Выбирете курс</h2>
            <br>
        </div>
        <div class="col-lg-2">
            <div class="list-group">
                <a href="/" class="list-group-item navbar-brand">На главную</a>
            </div>
        </div>
        <div class="col-lg-9">
            <?php
                include_once 'inc/router.php';
            $link->close();
            ?>
        </div>
    </div>
    </body>
    </html>