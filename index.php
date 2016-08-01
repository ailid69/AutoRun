<?php 
	/*-------------------------------------------------------------------------------------------------
		Page d'accueil 
		Si l'utilisateur n'est pas connecété on affiche un formulaire de connexion 
	-------------------------------------------------------------------------------------------------*/	
	require_once 'config.php'; 
?> 

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>AutoRun - Accueil</title>
    <meta name="description" content="My EDF application for Packages">
    <meta name="author" content="david.aili@hpe.com">

	<link href="/css/bootstrap.min.css" rel="stylesheet" media="screen">
    <style type="text/css">        
        .hero-unit { background-color: #fff; }
        .center { display: block; margin: 0 auto; }
		 body { padding-top: 70px; }
    </style>
</head>

<body>
    <script src="/js/jquery.min.js"></script>
	<script src="/js/bootstrap.min.js"></script>
<?php include('myNavBar.php'); ?>

<div class="container hero-unit">
	<div class="panel panel-default">
		<div class="panel-heading">
			<h1>Bienvenue dans l'application AutoRun</h1>
		</div>
		<div class="panel-body">
 
 <?php 
 if (isset($_GET['msg'])){
	 $msg = displayErrorMessage($_GET['msg']);
	 echo'	<div class="alert alert-danger" role="alert">Vous avez été redirigé vers la page d\'accueil.<BR> '.$msg.'</div>';
 }
 
  if (isset($_GET['info'])){
	 $msg = displayInfoMessage($_GET['info']);
	 echo'	<div class="alert alert-success" role="alert">'.$msg.'</div>';
 }
 
 if (isset($_SESSION['user'])){
	echo '	
		<h4>Veuillez utiliser la barre de navigation pour accéder aux différentes fonctions de l\'application</h4>
		<p>Vous pourrez nottament charger de nouveaux packages et suivre l\'évolution du traitement de ces packages depuis la phase de chargement jusqu\'à l\'execution.</p>
	</div>
	</div>
	</div>
	</body>
	</html>';
	die;
}
 ?>   
		<p><h2>Connexion</h2></p>

				<form action="/login.php" method="post"> 
					<div class="form-group">
						<label for="username" class="control-label">Utilisateur</label>
						<div class="row">
							<div class="col-md-6">
								<input type="text" class="form-control" id="username" name="username" placeholder="Veuillez saisir un nom d'utilisateur" required>
								<div class="help-block with-errors"></div>
							</div>
						</div>
					<div class="form-group">
						<label for="password" class="control-label">Mot de passe</label>
						<div class="row">
							<div class="col-md-6">
								<input type="password" class="form-control" name="password" id="password" placeholder="Mot de passe" required>
								<div class="help-block"></div>
							</div>
						</div>
					</div>
					<input type="submit" class="btn btn-info" value="Login" /> 
				</form> 

 </div>
</body>
</html>
