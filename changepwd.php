<?php
    require_once 'config.php';
	if (empty($_GET['id'])){
		header("Location: index.php?msg=2");
        die("Redirecting to index.php");	
	}
   
	if(
		empty($_SESSION['user']) ||
		$_SESSION['user']['isadmin']!=1 && (
			(!empty($_GET['id']) && $_SESSION['user']['id']!=$_GET['id']) ||
			(!empty($_POST['userid']) && $_SESSION['user']['id']!=$_POST['userid'])
		)
	)
	{
        header("Location: index.php?msg=3");
        die("Redirecting to index.php");	
	}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>AutoRun - Modifier le mot de passe</title>
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
   <p><h2>Modifier le mot de passe</h2></p>
  <?php 
	
        $query = "SELECT username, password, salt FROM users WHERE id='{$_GET['id']}'";
       	
		try {  
            $stmt = $db->prepare($query); 
            $result = $stmt->execute(); 
			$row=$stmt->fetch();
        } 
        catch(PDOException $ex){ 
		die("Failed to run query: " . $ex->getMessage()); } 

	if(!empty($_POST['newpassword'])){
		if ($_SESSION['user']['isadmin']==0){
		// check current password
			$pwd_ok = 0;
			if($row){ 
				$check_password = hash('sha256', $_POST['currentpassword'] . $row['salt']); 
				for($round = 0; $round < 65536; $round++){
					$check_password = hash('sha256', $check_password . $row['salt']);
				} 
				if($check_password === $row['password']){
					$pwd_ok = 1;
				}				
			}
		}
		else{
			$pwd_ok = 1;
		}
		
		if ($pwd_ok==1){
					 $salt = dechex(mt_rand(0, 2147483647)) . dechex(mt_rand(0, 2147483647)); 
					 $password = hash('sha256', $_POST['newpassword'] . $salt); 
					 for($round = 0; $round < 65536; $round++){ $password = hash('sha256', $password . $salt); } 
					 
					 $query = "UPDATE users set password='{$password}', salt='{$salt}' WHERE id='{$_GET['id']}'";
					 	
					try {  
						$stmt = $db->prepare($query); 
						$result = $stmt->execute(); 
					} 
					catch(PDOException $ex){ 
						die("Failed to run query: " . $ex->getMessage()); 
					} 
		}
           		
         
	}	

	?>
  
  </div>
  <div class="panel-body">
  
  <?php 
  if (isset($pwd_ok) && $pwd_ok == 0){
		echo 
			'
			<div class="alert alert-danger" role="alert">
			Le mot de passe saisie pour l\'utilisateur <strong>' .$_POST['username'] . ' </strong> est incorrect.
			</div> 
			';
	}
	   if (isset($pwd_ok) && $pwd_ok == 1){
		echo 
			'
			<div class="alert alert-info" role="alert">
			Le mot de passe a bien été modifié pour l\'utilisateur <strong>' .$_POST['username'] . ' </strong>.
			</div> 
			';
	}
	?> 
  
  
 <form data-toggle="validator" role="form" action="changepwd.php?id=<?php echo $_GET['id']?>" method="post">
<div class="row">
	<div class="form-group col-md-3">
		<label for="username" class="control-label">Utilisateur</label>
		<input type="text" class="form-control" id="username" name="username" value="<?php echo $row['username'];?>" readonly>
		<input type="hidden" class="form-control" id="userid" name="userid" value="<?php echo $row['id'];?>">
		<div class="help-block with-errors"></div>
	</div>
</div>


 <?php 
  if  ($_SESSION['user']['isadmin']!=1){
	  
  echo'
	<div class="row">
		<div class="form-group col-md-3">
			<label for="password" class="control-label">Mot de passe actuel</label>   
			<input type="password"  class="form-control" name="currentpassword" id="currentpassword" placeholder="Mot de passe actuel" required>
			<div class="help-block with-errors"></div>
	   </div>
	</div>
  ';
  }
  ?>
	<div class="row">
		<div class="form-group col-md-3">
			<label for="password" class="control-label">Nouveau mot de passe</label>   
			<input type="password" data-minlength="6" class="form-control" name="newpassword" id="newpassword" placeholder="Nouveau mot de passe" required>
			<div class="help-block">6 charactères minimum</div>
			<input type="password" class="form-control" id="inputPasswordConfirm" data-error="Veuillez confirmer le nouveau mot de passe" data-match="#newpassword" data-match-error="Les mots de passes ne sont pas identiques" placeholder="Confirmer le mot de passe" required>
			<div class="help-block with-errors"></div>
		</div>
	</div>

	<div class="row">
		<div class="form-group col-md-3">
			<button type="submit" class="btn btn-primary">Valider</button>
		</div>	
	</div>
</form>
</div>
</div>
</div>

</body>
</html>