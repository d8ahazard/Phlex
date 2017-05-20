<?php
    require_once dirname(__FILE__) . '/vendor/autoload.php';
    require_once dirname(__FILE__) . '/util.php';
    session_start();
    ini_set("log_errors", 1);
    ini_set('max_execution_time', 300);
    $errfilename = 'Phlex_error.log';
    ini_set("error_log", $errfilename);
    date_default_timezone_set("America/Chicago");
?>

<!doctype html>
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Phlex">
        <link rel="apple-touch-icon" sizes="180x180" href="./img/apple-icon.png">
        <link rel="icon" type="image/png" href="./img/favicon-32x32.png" sizes="32x32">
        <link rel="icon" type="image/png" href="./img/favicon-16x16.png" sizes="16x16">
        <link rel="manifest" href="./manifest.json">
        <link rel="mask-icon" href="./img/safari-pinned-tab.svg" color="#5bbad5">
        <link rel="shortcut icon" href="./img/favicon.ico">
        <meta name="msapplication-config" content="./img/browserconfig.xml">
        <meta name="theme-color" content="#ffffff">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <link href="./css/bootstrap-reboot.css" rel="stylesheet">
        <link href="./css/bootstrap.min.css" rel="stylesheet">
        <link href="./css/font-awesome.min.css" rel="stylesheet">
        <link rel="stylesheet" type="text/css" href="//fonts.googleapis.com/css?family=Roboto:300,400,500,700">
        <link rel="stylesheet" type="text/css" href="//fonts.googleapis.com/icon?family=Material+Icons">
        <link href="./css/material.css" rel="stylesheet">
        <link href="./css/snackbar.min.css" rel="stylesheet">
        <link href="./css/bootstrap-material-design.min.css" rel="stylesheet">
        <link href="./css/bootstrap-dialog.css" rel="stylesheet">
        <link href="./css/ripples.min.css" rel="stylesheet">
        <link href="./css/main.css" rel="stylesheet">
        <link href="./css/jquery-ui.min.css" rel="stylesheet">


        <!--[if lt IE 9]>
        <link href="/css/bootstrap-ie8.css" rel="stylesheet">
        <script src="https://cdn.jsdelivr.net/g/html5shiv@3.7.3,respond@1.4.2"></script>
        <![endif]-->

        <script type="text/javascript" src="./js/jquery-3.1.1.min.js"></script>
        <script type="text/javascript" src="./js/jquery-ui.min.js"></script>
        <script type="text/javascript" src="./js/snackbar.min.js"></script>
        <script type="text/javascript" src="./js/clipboard.min.js"></script>
        <script type="text/javascript" src="./js/jquery.simpleWeather.min.js"></script>
        <script type="text/javascript" src="./js/tether.min.js"></script>
        <script type="text/javascript" src="./js/bootstrap.min.js"></script>
        <script type="text/javascript" src="./js/bootstrap-dialog.js"></script>
        <script type="text/javascript" src="./js/arrive.min.js"></script>
        <script type="text/javascript" src="./js/material.min.js"></script>
        <script type="text/javascript" src="./js/ripples.min.js"></script>
        <script type="text/javascript" src="./js/nouislider.min.js"></script>


    </head>

    <body style="background-color:black">
        <div id="bgwrap">
            <div class="bg"></div>
        </div>
        <?php
            if (!(isset($_SESSION['plex_token'])) || isset($_GET['logout'])) {
                include_once('login.php');
                die();
            } else {
                define('LOGGED_IN', true);
                require_once dirname(__FILE__) . '/body.php';
                echo makeBody();
            }
        ?>
    </body>

</html>
