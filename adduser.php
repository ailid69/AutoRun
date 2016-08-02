<?php
	/*-------------------------------------------------------------------------------------------------
		Page pour l'ajout d'utilisateur (réservé aux admin)
	-------------------------------------------------------------------------------------------------*/		
    require_once 'config.php';

	/*-------------------------------------------------------------------------------------------------
		Redirige vers la page d'accueil si l'utilisateur n'est pas connecté 
		ou si l'utilisateur est connecté en tant que simple utilisateur 
	-------------------------------------------------------------------------------------------------*/		
	if(empty($_SESSION['user'])||$_SESSION['user']['isadmin']!=1) 
    {
        header("Location: index.php?msg=1");
        die("Redirecting to index.php"); 
    }
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>AutoRun - Créer un utilisateur</title>
    <meta name="description" content="My EDF application for Packages">
    <meta name="author" content="david.aili@hpe.com">

	<link href="/css/bootstrap.min.css" rel="stylesheet" media="screen">	
	<style>
        .hero-unit { background-color: #fff; }
        .center { display: block; margin: 0 auto; }
		 body { padding-top: 70px; }
    </style>
	
</head>

<body>
    <script src="/js/jquery.min.js"></script>
	<script src="/js/bootstrap.min.js"></script>
	<script src="/js/validator.min.js"></script>
<?php include('myNavBar.php'); ?>
         
<div class="container hero-unit">
	<div class="panel panel-default">
		<div class="panel-heading">
			<p><h2>Création d'un utilisateur</h2></p>
		</div>
		<div class="panel-body">
   <?php 
   /*-------------------------------------------------------------------------------------------------
		Si des données ont été postées alors il faut insérer un enregistrement en base
	-------------------------------------------------------------------------------------------------*/		
	if(!empty($_POST)){  
        $query = " 
            INSERT INTO users ( 
                username,
				firstname,
				lastname,
				email,
				isadmin,
                password, 
                salt 
            ) VALUES ( 
                :username, 
				:firstname,
				:lastname,
				:email,
				:isadmin,
                :password, 
                :salt
            ) 
        "; 
		/*-------------------------------------------------------------------------------------------------
			Mesures de sécurité pour calculer le mot de passe à stocker en base
		-------------------------------------------------------------------------------------------------*/		
        $salt = dechex(mt_rand(0, 2147483647)) . dechex(mt_rand(0, 2147483647)); 
        $password = hash('sha256', $_POST['password'] . $salt); 
        for($round = 0; $round < 65536; $round++){ $password = hash('sha256', $password . $salt); } 
        
        try {  
            $stmt = $db->prepare($query); 
			$stmt->bindValue(':username', $_POST['username'], PDO::PARAM_INT);
			$stmt->bindValue(':firstname', $_POST['firstname'], PDO::PARAM_INT);
			$stmt->bindValue(':lastname', $_POST['lastname'], PDO::PARAM_INT);
			$stmt->bindValue(':email', $_POST['email'], PDO::PARAM_INT);
			$stmt->bindValue(':password', $password, PDO::PARAM_INT);
			if (isset($_POST['isadmin']) && $_POST['isadmin'] =="isadmin")	{
				$stmt->bindValue(':isadmin', 1, PDO::PARAM_INT);
			}
			else{
				$stmt->bindValue(':isadmin', 0, PDO::PARAM_INT);
			}
			$stmt->bindValue(':salt', $salt, PDO::PARAM_INT);
            $result = $stmt->execute(); 
			echo 
			'
			<div class="alert alert-info" role="alert">
			L\'utilisateur <strong>' .$_POST['username'] . ' </strong> a bien été crée.
			</div> 
			';
        } 
        catch(PDOException $ex){ 
		die("Failed to run query: " . $ex->getMessage()); } 
		}
	?>
  
 <form data-toggle="validator" role="form" action="adduser.php" method="post">
	<div class="row">
		<div class="form-group col-md-3">
			<label for="username" class="control-label">Utilisateur</label>
			<input type="text" class="form-control" id="username" name="username" placeholder="Veuillez saisir un nom d'utilisateur" data-remote="checkusername.php" data-error="Ce nom d'utilisateur est déjà utilisé" required>
			<div class="help-block with-errors"></div>
		</div>

		<div class="form-group col-md-3">
			<label for="firstname" class="control-label">Prénom</label>
			<input type="text" style="text-transform: capitalize" class="form-control" id="firstname" name="firstname" placeholder="" data-error="Veuillez saisir un prénom" required>
			<div class="help-block with-errors"></div>
		</div>
		
		 <div class="form-group col-md-3">
			<label for="lastname" class="control-label">Nom</label>
			<input type="text" style="text-transform: uppercase" class="form-control" id="lastname" name="lastname" placeholder="" data-error="Veuillez saisir un nom" required>
			<div class="help-block with-errors"></div>
		</div>
		
		<div class="form-group col-md-3">
			<label for="inputEmail" class="control-label">Email</label>
			<input type="email" class="form-control" id="inputEmail" name="email" placeholder="john.doe@hpe.com" data-remote="checkemail.php" data-error="L'addresse est incorrecte ou déjà utilisée" required>
			<div class="help-block with-errors"></div>
		</div>
	</div>
  
	<div class="row">
		<div class="form-group col-md-3">
			<label for="password" class="control-label">Mot de passe</label>
			<input type="password" data-minlength="6" class="form-control" name="password" id="password" placeholder="Mot de passe" required>
			<div class="help-block">6 caractères minimum</div>
		</div>
		<div class="form-group col-md-3">
			<label for="inputPasswordConfirm" class="control-label">Confirmer le mot de passe</label>
			<input type="password" class="form-control" id="inputPasswordConfirm" data-error="Veuillez confirmer le mot de passe" data-match="#password" data-match-error="Les mots de passes ne sont pas identiques" placeholder="Confirmer le mot de passe" required>
			<div class="help-block with-errors"></div>
		</div>
	</div>
  
	<div class="row">
		<div class="form-group col-md-6" >
			<div class="checkbox">	
				<label class="checkbox-inline"><input type="checkbox" value="isadmin" name="isadmin">Administateur de l'application</label>
				<div class="help-block with-errors"></div>
			</div>
		</div>
	</div>
	
	<div class="row">
		<div class="form-group col-md-6">
			<button type="submit" class="btn btn-primary">Valider</button>
		</div>
	</div>

</form>
</div>
</div>
</div>

</body>
</html>

