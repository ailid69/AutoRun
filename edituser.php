<?php
	/*-------------------------------------------------------------------------------------------------
		Page pour editer les informations d'un utilisateur
		La page est accessible pour les administrateurs ou simple utilisateur
		Un utilisateur peut modifier seulement les informations de son utilsateur et n'a pas accès à l'option Administrateur
		(seul l'administrateur peut changer le rôle d'un utilisateur)
	-------------------------------------------------------------------------------------------------*/		   

   require_once 'config.php';
   
	/*-------------------------------------------------------------------------------------------------
		Redirige vers la page d'accueil si l'utilisateur n'est pas connecté 
		ou si l'utilisateur est connecté en tant que simple utilisateur 
		et qu'il demande d'éditer un autre tilisateur que lui même
	-------------------------------------------------------------------------------------------------*/		
  
	if(
		empty($_SESSION['user']) ||
		$_SESSION['user']['isadmin']!=1 && (
			(!empty($_GET['id']) && $_SESSION['user']['id']!=$_GET['id']) ||
			(!empty($_POST['userid']) && $_SESSION['user']['id']!=$_POST['userid'])
		)
	)
	{
	    header("Location: index.php?msg=7");
        die("Redirecting to index.php");
	}
	
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>AutoRun - Editer un utilisateur</title>
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

  <!-- Default panel contents -->
  <div class="panel-heading">
   <p><h2>Editer un utilisateur</h2></p>
  </div>
  <?php 
	
	/*-------------------------------------------------------------------------------------------------
		Si un id d'utilisateur est passé dans l'URL 
		alors on récupère les informations de cet utilisateur depuis la base pour les afficher dans le formulaire
	-------------------------------------------------------------------------------------------------*/		
	if(!empty($_GET['id'])){  
        $query = "SELECT id,username,firstname,lastname,email,isadmin FROM users WHERE id='{$_GET['id']}'";
		
        try {  
            $stmt = $db->prepare($query); 
            $result = $stmt->execute(); 
			if ($stmt->rowCount()!=1){
				$errmsg = "L'utilisateur recherché n'existe pas!";
			}
			$row=$stmt->fetch();
        } 
        catch(PDOException $ex){ 
			$errmsg = 'Il y a eu un problême avec la base de données. <BR><code>'.$ex->getMessage().'</code>';
		} 
	}
	/*-------------------------------------------------------------------------------------------------
		Si des données sont envoyées en POST c'est que l'utilisateur à cliquer pour sauvegarder les modifications
		Il faudra alors faire un UPDATE en base
	-------------------------------------------------------------------------------------------------*/		
	else if(!empty($_POST['userid'])){
		if (!empty($_POST['isadmin'])){
			$ismyuseradmin = 1;
		}
		else{
			$ismyuseradmin = 0;
		}
		
	/*-------------------------------------------------------------------------------------------------
		Si l'utilisateur est admin, il a la possibilité de modifier le role (admin / user) de l'utilisateur en cours de modification 
		La requete est différente selon le cas
	-------------------------------------------------------------------------------------------------*/		
		
		if ($_SESSION['user']['isadmin']==1){
			$query= "UPDATE users set firstname = '{$_POST['firstname']}',lastname='{$_POST['lastname']}',email='{$_POST['email']}',isadmin={$ismyuseradmin} WHERE id='{$_POST['userid']}'";
		}
		else{
			$query= "UPDATE users set firstname = '{$_POST['firstname']}',lastname='{$_POST['lastname']}',email='{$_POST['email']}' WHERE id='{$_POST['userid']}'";
		}
		
		try {  
			$stmt = $db->prepare($query); 
            $result = $stmt->execute(); 
	
        } 
        catch(PDOException $ex){ 
			$errmsg = 'Il y a eu un problême avec la base de données. <BR><code>'.$ex->getMessage().'</code>';
		}
		
	/*-------------------------------------------------------------------------------------------------
		Après modification des informations, on affiche les données de l'utilisateur 	
	-------------------------------------------------------------------------------------------------*/		
		$query = "SELECT id,username,firstname,lastname,email,isadmin FROM users WHERE id='{$_POST['userid']}'";
		
        try {  
            $stmt = $db->prepare($query); 
            $result = $stmt->execute(); 
			$row=$stmt->fetch();
			
        } 
        catch(PDOException $ex){ 
			$errmsg = 'Il y a eu un problême avec la base de données. <BR><code>'.$ex->getMessage().'</code>';
		}
	} 
	else{
		header("Location: index.php");
        die("Redirecting to index.php");	
	}
	?>
  
  <div class="panel-body"> 
  <?php 
  
	/*-------------------------------------------------------------------------------------------------
		Si le paramètre userid est passé en POST, c'est que l'on vient de modifier les données d'un utilisateur
		Dans ce cas on affiche un bandeau d'information		
	-------------------------------------------------------------------------------------------------*/		
	
  
  if (!empty($_POST['userid'])){
		echo 
			'
			<div class="alert alert-info" role="alert">
			L\'utilisateur <strong>' .$_POST['username'] . ' </strong> a bien été modifié.
			</div> 
			';
	}
	
	/*-------------------------------------------------------------------------------------------------
		Si il y a eu une erreur, on affiche un bandeau d'erreur et on stoppe l'execution
	-------------------------------------------------------------------------------------------------*/		
	if (isset($errmsg)){
			echo '<div class="alert alert-danger" role="alert">'.$errmsg.'</div>';
			echo '</div></div></div></body></html>';
			die;
	}
	?> 
  
   
 <form data-toggle="validator" role="form" action="edituser.php" method="post">

  <div class="form-group">
    <label for="username" class="control-label">Utilisateur</label>
    <input type="text" class="form-control" id="username" name="username" value="<?php echo $row['username'];?>" readonly>
	<!-- On envoit le userid sous la forme d'un input hidden -->
	<input type="hidden" class="form-control" id="userid" name="userid" value="<?php echo $row['id'];?>">
	<div class="help-block with-errors"></div>
  </div>
  
  <div class="form-group">
    <label for="firstname" class="control-label">Prénom</label>
    <input type="text" style="text-transform: capitalize" class="form-control" id="firstname" name="firstname" value="<?php echo $row['firstname'];?>" data-error="Veuillez saisir un prénom" required>
	<div class="help-block with-errors"></div>
  </div>
  
   <div class="form-group">
    <label for="lastname" class="control-label">Nom</label>
    <input type="text" style="text-transform: uppercase" class="form-control" id="lastname" name="lastname" value="<?php echo $row['lastname'];?>" data-error="Veuillez saisir un nom" required>
	<div class="help-block with-errors"></div>
  </div>
 
  <div class="form-group">
    <label for="inputEmail" class="control-label">Email</label>
    <input type="email" class="form-control" id="inputEmail" name="email" value="<?php echo $row['email'];?>" data-remote="checkemail.php?id=<?php echo $row['id'];?>" data-error="Un utilisateur utilise déjà cette addresse (ou addresse malformée)" required>
    <div class="help-block with-errors"></div>
  </div>
  
  
  <?php 
  
  	/*-------------------------------------------------------------------------------------------------
		La case à cocher "Administrateur de l'application" ne s'affiche que si l'utilisateur à le rôle d'administrateur
	-------------------------------------------------------------------------------------------------*/
	
  if  ($_SESSION['user']['isadmin']==1){
	  
  echo'
  <div class="form-group">
  
    <div class="checkbox">
	<label class="checkbox-inline"><input type="checkbox" ';
	
	if ($row['isadmin']==true){
		echo ' checked=true ';
	}
	echo '
	 name="isadmin">Administateur de l\'application</label>
      <div class="help-block with-errors"></div>
    </div>
  </div>
  ';
  }
  ?>
  <div class="form-group">
    <button type="submit" class="btn btn-primary">Valider</button>
  </div>
</form>
</div>
</div>
</div>

</body>
</html>