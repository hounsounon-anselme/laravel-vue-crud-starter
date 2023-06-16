<?php
session_start();
require('connect.php');
/*
// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['userLogin'])) {
    header('Location: in.php'); // Rediriger vers la page de connexion
    exit();
}*/

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Récupérer les données du formulaire de mise à jour du mot de passe
    $nouveauMotDePasse = $_POST['nouveau-mot-de-passe'];

    // Effectuer des opérations supplémentaires si nécessaire, comme la validation des données

    // Mettre à jour le mot de passe dans la base de données pour l'utilisateur connecté
    $userLogin = $_SESSION['userLogin'];
    $sql = "UPDATE users SET password = '$nouveauMotDePasse' WHERE login = '$userLogin'";
    
    if (mysqli_query($conn, $sql)) {
        echo "Le mot de passe a été mis à jour avec succès.";
    } else {
        echo "Erreur lors de la mise à jour du mot de passe : " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Mise à jour du mot de passe</title>
</head>
<body>
    <h2>Mise à jour du mot de passe</h2>
    <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
        <label for="nouveau-mot-de-passe">Nouveau mot de passe :</label>
        <input type="password" name="nouveau-mot-de-passe" id="nouveau-mot-de-passe" required>
        <br><br>
        <input type="submit" value="Mettre à jour le mot de passe">
    </form>
</body>
</html>










<?php
session_start();
require('connect.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Récupérer les données du formulaire de connexion
    $userLogin = $_POST['login-username'];
    $mot_de_passe = $_POST['login-password'];

    // Effectuer des opérations supplémentaires si nécessaire, comme la validation des données

    // Vérifier les données de connexion dans la base de données
    $sql = "SELECT * FROM users WHERE login = '$userLogin' ";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) == 1) {
        // Données de connexion valides, créer une session pour l'utilisateur
        $_SESSION['userLogin'] = $userLogin;
        header('Location: page_accueil.php');
        exit();
    } else {
        // Données de connexion invalides, afficher un message d'erreur
        $erreur = "Nom d'utilisateur ou mot de passe incorrect.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Page de connexion</title>
</head>
<body>
    <h2>Connexion</h2>
    <?php if (isset($erreur)) { echo "<p>$erreur</p>"; } ?>
    <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
        <label for="login-username">Nom d'utilisateur :</label>
        <input type="text" name="login-username" id="login-username" required>
        <br><br>
        <label for="login-password">Mot de passe :</label>
        <input type="password" name="login-password" id="login-password" required>
        <br><br>
        <input type="submit" value="Se connecter">
    </form>
</body>
</html>


//index


<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'functions.php';
reconnect_from_cookie();

$msg = "";

if (isset($_POST['valider'])) {

    include("connect.php");

    $sql = mysqli_query($conn, "SELECT * FROM users WHERE login='" . $_POST['login'] . "' AND password = '" . $_POST['pass'] . "' AND etat = 1") or die(mysqli_error($conn));

    if (mysqli_num_rows($sql) != 0) {

        $data = mysqli_fetch_array($sql);
        $last_connexion = 'UPDATE users SET last_connexion = "' . date("Y-m-d H:i:s") . '" WHERE id="' . $data['id'] . '" ';
        mysqli_query($conn, $last_connexion) or die(mysqli_error($conn));

        $_SESSION['auth'] = $data; // Les infos de l'utilisateur connecte
        $_SESSION['auth']['iduser'] = $data['idrh'];
        $_SESSION['auth']['code'] = $data['idrh'];
        $_SESSION['PROFIL'] = $data['IDProfil'];
        $_SESSION['AGENT'] = $data['id'];
        $_SESSION['NOM'] = $data['nom'];
        $_SESSION['PRENOM'] = $data['prenom'];
        $_SESSION['IDCAMPAGNE'] = $data['IDCampagne'];
        $_SESSION['pays'] = $data['pays'];

        // pour la redirection vers ARES et soeurs
        $_SESSION['CODE'] = password_hash($_SESSION['auth']['idrh'], PASSWORD_DEFAULT);
        //return var_dump($_SESSION['CODE']);
        // on insère dans la table logs
        $query1 = "INSERT INTO logs (IDProfil, idrh, nom, prenom, dateaction, code, iduser) VALUES ('" . $data['IDProfil'] . "', '" . $data['idrh'] . "','" . addslashes($data['nom']) . "','" . addslashes($data['prenom']) . "', '" . date('Y-m-d H:i') . "','" . $_SESSION['CODE'] . "', '" . $data['id'] . "' )";
        mysqli_query($conn, $query1) or die(mysqli_error($conn));

        /*
        * 
        *  Save session in database 
        *
        */
        // Define connection
        define('SESSION_DB_USERNAME', 'tpartyapps');
        define('SESSION_DB_PASSWORD', 'mypass'); //mot de passe ? mettre 
        define('SESSION_DSN', 'mysql:host=10.0.5.29;dbname=session_manager;charset=utf8');

        try {
            $session_con = new PDO(SESSION_DSN, SESSION_DB_USERNAME, SESSION_DB_PASSWORD, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
        } catch (Exception $e) {
            die('Erreur : ' . $e->getMessage());
        }

        // Insert data
        $stmt = $session_con->prepare("INSERT INTO sessions(data) values(?)");
        $stmt->execute(array(json_encode($_SESSION)));

        //Get session inserted
        $stmt = $session_con->prepare("SELECT idsession, data FROM sessions ORDER BY idsession DESC LIMIT 0, 1");
        $stmt->execute();
        $last_session_inserted = $stmt->fetch(PDO::FETCH_OBJ);

        // Save in this session variable
        $_SESSION['dbdata'] = $last_session_inserted;

        // Si l'utilisateur veut reste connecte
        if (isset($_POST['connected'])) {

            $remember_token = str_random(250);
            mysqli_query($conn, "UPDATE users SET stay_connect_token = '" . $remember_token . "' WHERE id = " . $data['id']) or die(mysqli_error($conn));

            setcookie('remember', $data['id'] . '==' . $remember_token . sha1($data['id'] . 'test'), time() + 60 * 60 * 24 * 1, '/', 'localhost', false, true);
        }

        if (($_POST['pass'] === "0000") or (strlen($_POST['pass']) < 4)) {
            header("Location: changeme.php");
            exit();
        }

        mysqli_close($conn);

        header("Location: dashboard.php");
        exit();
    } else {
        $msg = "Identifiant ou mot de passe incorrects";
    }
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <meta content="IE=edge" http-equiv="X-UA-Compatible">
    <meta content="initial-scale=1.0, width=device-width" name="viewport">
    <title>CONNEXION - INTRANET MEDIA CONTACT BENIN</title>

    <!-- css -->
    <!-- <link href="css/base.min.css" rel="stylesheet"> -->
    <link href="bower_components/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- favicon -->
    <link rel="shortcut icon" href="images/favicon.ico" type="image/vnd.microsoft.icon" />
    <!-- ... -->

    <!-- ie -->
    <!--[if lt IE 9]>
          <script src="js/html5shiv.js" type="text/javascript"></script>
          <script src="js/respond.js" type="text/javascript"></script>
        <![endif]-->

    <style media="screen">
        .navbar {
            border-radius: 0px;
            min-height: 75px !important;
        }

        .logo {
            text-align: center;
            padding-top: 5px;
            padding-bottom: 5px;
        }

        .content {
            margin-top: 3em;
        }

        .connexion-header {
            margin-bottom: 3em;
            color: #313435;
        }

        .information img {
            width: 100%;
        }

        .text-normal {
            font-weight: normal;
            color: #313435;
        }

        .form-control:focus {
            border-color: #1a1d1f;
            outline: 0;
            -webkit-box-shadow: inset 0 1px 1px rgba(0, 0, 0, .075), 0 0 8px rgba(36, 41, 45, 0.6);
            box-shadow: inset 0 1px 1px rgba(0, 0, 0, .075), 0 0 8px rgba(33, 36, 39, 0.6);
        }

        .navbar-default {
            background-color: #fff !important;
            border-bottom: none;
            margin-top: 3rem;
        }

        .footer-bg {
            margin-top: 50px;
            min-height: 250px;
            background-image: url('images/portail_home_footer_bg.jpg');
            background-repeat: no-repeat;
            background-size: contain;
            background-position: center;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
        }
    </style>

    <!-- Matomo -->
    <script type="text/javascript">
        var _paq = window._paq = window._paq || [];
        /* tracker methods like "setCustomDimension" should be called before "trackPageView" */
        _paq.push(['trackPageView']);
        _paq.push(['enableLinkTracking']);
        (function() {
            var u = "//matomo/";
            _paq.push(['setTrackerUrl', u + 'matomo.php']);
            _paq.push(['setSiteId', '2']);
            var d = document,
                g = d.createElement('script'),
                s = d.getElementsByTagName('script')[0];
            g.type = 'text/javascript';
            g.async = true;
            g.src = u + 'matomo.js';
            s.parentNode.insertBefore(g, s);
        })();
    </script>
    <!-- End Matomo Code -->

</head>

<body>
    <header class="header navbar navbar-default" role="navigation">
        <div class="logo">
            <span class=""><img src="images/logo_mc_Offshore_company.png" width="250px" /></span>
        </div>
    </header>
    <div class="content">
        <div class="content-inner">
            <div class="container">
                <div class="row">
                    <div class="col-md-7 information">
                        <img src="images/lecon_du_jour.png">
                    </div>
                    <div class="col-md-4">
                        <div class="card-wrap">
                            <div class="card">
                                <div class="card-main">
                                    <div class="card-header">
                                        <div class="card-inner">
                                            <h1 class="card-heading">INTRANET MCB</h1>
                                        </div>
                                    </div>
                                    <div class="card-inner">

                                        <h4 class="connexion-header">S'identifier</h4>
                                        <form class="form" action="" method="post" autocomplete="off">
                                            <div class="form-group">
                                                <label class="text-normal" for="login-username">Nom d'utilisateur</label>
                                                <input class="form-control" name="login" id="login-username" type="text" required>
                                            </div>
                                            <div class="form-group">
                                                <label class="text-normal" for="login-password">Mot de passe</label>
                                                <input class="form-control" name="pass" id="login-password" type="password" required>
                                            </div>
                                            <div class="form-group">
                                                <!-- <label class="text-normal">
                              <input type="checkbox" name="connected" id="connected">
                              Restez connecter
                            </label> -->
                                                <button class="btn btn-primary btn-block pull-right" type="submit" name="valider">Connexion</button>
                                            </div>

                                            <div class="form-group">
                                                <div class="row">
                                                    <div class="col-md-10">
                                                    </div>
                                                </div>
                                            </div>

                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if (isset($msg) && !empty($msg)) : ?>
                            <div class="col-md-12">
                                <div class="alert alert-danger">
                                    <?= $msg; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <section class="footer-bg"></section>
    <footer class="footer">
        <div class="container">
            <p>Copyright &copy; MEDIA CONTACT BENIN | 2015 - <?php echo date('Y'); ?>. Powered By DSI. </p>
        </div>
    </footer>

    <script src="js/base.min.js" type="text/javascript"></script>
</body>

</html>















<?php


if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['auth'])) {
  $_SESSION['flash']['danger'] = "Merci de vous connecter";
  header('Location: index.php');
  exit();
}

  //$userId = $_SESSION['id']; // on conserve sa session dans une autre variable
  if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Récupérer les données du formulaire de connexion
    $userLogin = $_POST['login']['id'];
    $mot_de_passe = $_POST['password'];}
 


// Vérifier si le formulaire a été soumis
if (isset($_POST['valider'])) {
    // Récupérer les valeurs du formulaire
    //$userLogin = $_POST['email'];
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

}

function updatePassword($value){
    
    $value = trim($value);
    $value = str_replace(" ", "", $value);

    if (strlen($value) < 6) {
        return false;
    }
    return true;

  }  
    //hash
    
    include 'connect.php';
    if (!$conn) {
      die('<p>Échec de la connexion à la base de données: </p>' . mysqli_connect_error());
    }

    $query0 = 'SELECT password FROM users WHERE id = "' . $userLogin . '"';
    $result = mysqli_query($conn, $query0) or die ("problème1 avc la requète ".$query0);

    if(mysqli_num_rows($result) == 1){ 
      echo "ok" ;
    }else{
      die ("problème1 avc la requète ".$query0);
    }
       

    if ( ($result===$currentPassword) && updatePassword($newPassword) && ($newPassword === $confirmPassword)) {
      $hashPassword= password_hash($newPassword, PASSWORD_DEFAULT);

      //$query='update users SET  password ="'.$hashPassword.'" where id = "'.$userLogin.'"';
      $query = 'UPDATE users SET password = "'. $hashPassword .'" WHERE login = "' . $userLogin . '"';
      $resu=mysqli_query($conn,$query) or die ("problème2 avc la requète ".$query);;
        if(mysqli_num_rows($resu) == 1){
          //$row = mysqli_fetch_assoc($resu);
          //$hashPassword= $row['password'];
              // Traiter les données
            
               
                echo "Mot de passe mis a joue avec succès <br>";
      // ...
              
                }else{
                  echo "<p>Echec de mise a jour</p> " . mysqli_error($conn);
                }
              }
      else {
        // Gérer l'échec de la requête
          echo "<p>Erreur lors de l'exécution de la requête:</p> " . mysqli_error($conn);
      }
        // Fermer la connexion à la base de données
        //header("Location: dashboard.php");
        mysqli_close($conn);
?>




<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="DRI">
    <!-- InstanceBeginEditable name="doctitle" -->
    <title>MCB APPS | Changement mot de passe</title>
    <!-- InstanceEndEditable -->
    <!-- Bootstrap Core CSS -->
    <link rel="shortcut icon" href="images/favicon.ico" type="image/vnd.microsoft.icon" />
    <link href="bower_components/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="bower_components/bootstrap/dist/css/toastr.css" rel="stylesheet">
    <!-- MetisMenu CSS -->
    <link href="bower_components/metisMenu/dist/metisMenu.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="dist/css/sb-admin-2.css" rel="stylesheet">

    <!-- Custom Fonts -->
    <link href="bower_components/font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css">
    <!-- DataTables CSS -->
    <link href="bower_components/datatables-plugins/integration/bootstrap/3/dataTables.bootstrap.css" rel="stylesheet">

    <!-- DataTables Responsive CSS -->
    <link href="bower_components/datatables-responsive/css/dataTables.responsive.css" rel="stylesheet">

    <link href="bower_components/bootstrap/dist/css/daterangepicker-bs3.css" rel="stylesheet" type="text/css" />
    <link href="bower_components/bootstrap/dist/css/bootstrap-switch.css" rel="stylesheet">
    <!-- Bootstrap time Picker -->
    <link href="bower_components/bootstrap/dist/css/bootstrap-timepicker.min.css" rel="stylesheet"/>
    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
        <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
   <!-- jQuery -->

    <script src="bower_components/jquery/dist/jquery.min.js"></script>

    <!-- Bootstrap Core JavaScript -->
    <script src="bower_components/bootstrap/dist/js/bootstrap.min.js"></script>

    <!-- Metis Menu Plugin JavaScript -->
    <script src="bower_components/metisMenu/dist/metisMenu.min.js"></script>

    <!-- Custom Theme JavaScript -->
    <script src="dist/js/sb-admin-2.js"></script>
    <script src="js/toastr.js"></script>
      <!-- DataTables JavaScript -->
    <script src="bower_components/datatables/media/js/jquery.dataTables.min.js"></script>
    <script src="bower_components/datatables-plugins/integration/bootstrap/3/dataTables.bootstrap.min.js"></script>

  </head>

  <body>

    <div id="wrapper" >
      <!-- Page Content -->
     <!-- Navigation -->
     <nav class="navbar navbar-default navbar-static-top" role="navigation" style="margin-bottom: 0;">
        <?php include("navbar.php");?>
            
            <!-- /.navbar-top-links -->

            <div class="navbar sidebar" role="navigation">
  
                <div class="sidebar-nav navbar-collapse">
                   <?php include("menu.php"); ?>
                </div>
                <!-- /.sidebar-collapse -->
            </div>
            <!-- /.navbar-static-side -->
            
        </nav>

        
      <div id="page-wrapper">
        <div class="container-fluid">
        <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
            <h1 class="page-header">Changer votre mot de passe</h1>

            <div class="panel panel-yellow">

              <div class="panel-heading"></div>

                <div class="panel-body">

                  <div class="col-md-6">
                    <table class="table table-users-information">
                      <tbody>
                      <tr>
                          <td colspan="2">
                            <div class="form-group">
    						              <label>Mot de passe actuel </label>
                              <input class="form-control" type="password" name="current_password" required>
                              <p class="help-block alert alert-info">Au moins 6 caracteres<br>Ne doit pas contenir "mcb"</p>
                            </div>
                          </td>
                        </tr>
                        <tr>
                          <td colspan="2">
                            <div class="form-group">
    						              <label>Nouveau mot de passe</label>
                              <input class="form-control" type="password" name="new_password" required>
                              <p class="help-block alert alert-info">Au moins 6 caracteres<br>Ne doit pas contenir "mcb"</p>
                            </div>
                          </td>
                        </tr>
                        <tr>
                          <td colspan="2">
                            <div class="form-group">
						                  <label>Confirmer nouveau mot de passe</label>
                              <input class="form-control" type="password" name="confirm_password" required>
                            </div>
                          </td>
                        </tr>
                    </tbody>
                  </table>
                  <span class="navbar-right">
                    <button class="btn btn-outline btn-warning" type="submit" name="Valider">
                      <i class="fa fa-save fa-fw"></i> Valider
                    </button>
                  </span>
                </div>


              </div>

            </div>

          </form>

        </div>
            <!-- /.container-fluid -->
      </div>
        <!-- /#page-wrapper -->
    </div>
    <!-- /#wrapper -->

  </body>

</html>
