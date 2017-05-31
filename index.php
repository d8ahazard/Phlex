<?php
    require_once dirname(__FILE__) . '/vendor/autoload.php';
    require_once dirname(__FILE__) . '/util.php';
?>

<!doctype html>
<html>
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
    <div class="modal fade" id="alertModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="alertTitle">Modal title</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="alertBody">
                    <p>Modal body text goes here.</p>
                </div>

            </div>
        </div>
    </div>
        <div id="bgwrap">
            <div class="bg bgLoaded"></div>
        </div>
        <?php
        $message = checkFiles();
        if ($message) {
            $scriptBlock = "<script language='javascript'>
                showMessage('ERROR','" . $message . "');
                function showMessage(title,message) {
                    $('#alertTitle').text(title);
                    $('#alertBody p').text(message);
                    $('#alertModal').modal('show');
                }
                </script>";
            echo $scriptBlock;
        }
        session_start();
        setDefaults();
        $config = new Config_Lite('config.ini.php');
            if (isset($_GET['logout'])) {
                $_SESSION['plexUserName'] = '';
                clearSession();
                $has_session = session_status() == PHP_SESSION_ACTIVE;
                if ($has_session) session_destroy();
                header('Location:  .');
            }
            if (! isset($_SESSION['plexToken'])) {
                echo '
                    <div class="loginBox">
                        <div class="login-box">
                        <div class="card loginCard">
                            <div class="card-block">
                                <b><h3 class="loginLabel card-title">Welcome to Phlex!</h3></b>
                                <img class="loginLogo" src="./img/phlex_logo.png" alt="Card image">
                                <h6 class="loginLabel card-subtitle text-muted">Please log in below to begin.</h6>
                            </div>
                            
                            <div class="card-block">
                                <form id="loginForm" method="post">
                                    <div class="label-static form-group loginGroup">
                                        <label id="userLabel" for="username" class="control-label">Username</label>
                                        <input type="text" class="form-control login-control" id="username" name ="username" autofocus/>
                                        <span class="bmd-help">Enter your Plex username or email address.</span>
                                    </div>
                                    <div class="label-static form-group loginGroup">
                                        <label id="passLabel" for="password" class="control-label">Password</label>
                                        <input type="password" class="form-control login-control" id="password" name="password"/>
                                        <span class="bmd-help">Enter your Plex password.</span>
                                    </div>
                                    <button class="btn btn-raised btn-primary" id="post">DO IT!</button>
                                    <br><br>
                                    <a href="http://phlexchat.com/Privacy.html" class="card-link">Privacy Policy</a>
                                </form>
                            </div>
                        </div>
                    </div>
                    <script type="text/javascript" src="./js/login.js"></script>';
                die();
            } else {
                define('LOGGED_IN', true);
                require_once dirname(__FILE__) . '/body.php';
                echo makeBody();
            }
        ?>

    </body>
</html>