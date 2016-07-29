<?php 
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
    <h1>AutoRun</h1>
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
	echo '</div></body></html>';
	die;
}
 ?>   
	<div class="panel panel-default">
		<div class="panel-heading">
			<p><h2>Connexion</h2></p>
		</div>
		<div class="panel-body">
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
	</div>
</div>

</body>
</html>