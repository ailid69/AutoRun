<?php
    ce 'config.php';
    if(empty($_SESSION['user'])||$_SESSION['user']['isadmin']!=1) 
    {
        header("Location: index.php");
        die("Redirecting to index.php"); 
    }
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>AutoRun - Gestion des utilisateurs</title>
    <meta name="description" content="My EDF application for Packages">
    <meta name="author" content="david.aili@hpe.com">
	<link href="/css/bootstrap.css" rel="stylesheet" media="screen">
    <style type="text/css">
        .hero-unit { background-color: #fff; }
        .center { display: block; margin: 0 auto; }
    </style>
</head>

<body>
    <script src="/js/jquery.min.js"></script>
	<script src="/js/bootstrap.min.js"></script>
	
<?php include('myNavBar.php'); ?>

<div class="container hero-unit">
	<div class="panel panel-default">

  <!-- Default panel contents -->
  <div class="panel-heading">
   <p><h2>Gestion des utilisateurs</h2></p>
  </div>
  <div class="panel-body">
<?php 
	if(!empty($_POST)){  
		if ($_POST['delete_users']){
			$emptyQuery = true;
			foreach ($_POST as $key => $value) {
				if (substr ( $key , 0, 4 ) == 'del_') 
				{
					$emptyQuery = false;
					$rest = substr($key, 4, strlen($key)-4);
					$usersToDel .=$db->quote($rest) .',';
					$usersToDelDisplayName .= $_POST['username_'.$rest] . ',';
				}
			}
				
			if ($emptyQuery==true){

				echo '
			
				<div class="alert alert-danger" role="alert">
					<span class="glyphicon glyphicon-remove" aria-hidden="true" style="color:red"></span> Il faut sélectionner des utilisateurs avant de cliquer sur Supprimer des utilisateurs.
				</div>

				';
		
			}
			else
			{
				$usersToDel = substr($usersToDel,0,strlen($usersToDel)-1);
				$usersToDelDisplayName = substr($usersToDelDisplayName,0,strlen($usersToDelDisplayName)-1);
				echo '
				<div class="alert alert-info" role="alert">
					<span class="glyphicon glyphicon-question-sign" aria-hidden="true"></span> Etes vous surs de vouloir supprimer définitivement le(s) utilisateur(s) suivant(s) ? : <strong>' .$usersToDelDisplayName .'</strong>
					<form action="users.php" method="post"> 				
						<input type="hidden" value="'.$usersToDel .'" name="usersToDel">
						<input type="hidden" value="'.$usersToDelDisplayName .'" name="usersToDelDisplayName">
						<input type="submit" class="btn btn-info" value="OK" name="deleteUsersOK">
						<input type="submit" class="btn btn-info" value="Annuler" name="deleteUsersCancel">
					</form>
				</div>
				';
				
			}
		}
		elseif ($_POST['deleteUsersOK'])
		{
			$query = "DELETE FROM users WHERE id IN (".$_POST['usersToDel'] ." )"; 
			
			try{ 
				$stmt = $db->prepare($query); 
				$result = $stmt->execute($query_params); 
			} 
			catch(PDOException $ex){ die("Failed to run query: " .$query .'.' . $ex->getMessage()); } 
			echo' 
			<div class="alert alert-success" role="alert">
					<span class="glyphicon glyphicon glyphicon-ok" aria-hidden="true"></span> Le(s) utilisateur(s) suivant(s) ont bien été supprimé(s): <strong> ' . $_POST['usersToDelDisplayName'] .'</strong> 
			</div>
			';
		}
		elseif ($_POST['deleteUsersCancel'])
		{
			header("Location: users.php");
		}
		elseif ($_POST['add_user'])
		{
			header("Location: adduser.php");
		}
		else
		{		
			foreach ($_POST as $key => $value) {
				if (substr ( $key , 0, 10 ) == 'edit_user_') 
				{
					$id = substr($key, 10, strlen($key)-10);
					header("Location: edituser.php?id=". $id);
				}
				else if (substr ( $key , 0, 4 ) == 'pwd_') 
				{
					$id = substr($key, 4, strlen($key)-4);
					header("Location: changepwd.php?id=". $id);
				}
			}
			
		}
	}

	
$query = " 
            SELECT 
				id,
                username,
				firstname,
				lastname,
				email,
				isadmin
            FROM users order by username
        "; 
 try{ 
            $stmt = $db->prepare($query); 
            $result = $stmt->execute($query_params); 
        } 
        catch(PDOException $ex){ die("Failed to run query: " . $ex->getMessage()); } 
	
	?>
 
 <!-- Table -->
  <form action="users.php" method="post"> 
  <table class="table">
    <thead> 
		<tr> 
			<th>Utilisateur</th> 
			<th>Prénom</th> 
			<th>Nom</th> 
			<th>Email</th> 
			<th>admin?</th> 
			<th>Editer</th> 
			<th>Changer le mot de passe</th>
			<th>Supprimer</th> 			
		</tr> 
	</thead> 
	<tbody> 
	<?php
	   while($row=$stmt->fetch())
        {
		echo '
		<tr> <th scope=row>' . $row['username'] . ' </th> 
			<td style="text-transform: capitalize">' . $row['firstname'] .'</td> 
			<td style="text-transform: uppercase">' . $row['lastname'] .'</td> 
			<td>' . $row['email'] .'</td> 
			<td>'
		;
			if($row['isadmin'])	{echo '<span class="glyphicon glyphicon glyphicon-ok" aria-hidden="true" style="color:green"></span>';}
			else				{echo '<span class="glyphicon glyphicon-remove" aria-hidden="true" style="color:black"></span>';} 
		echo '		
		</td> 
		<td> <input type="submit" class="btn btn-info" value="Editer" name="edit_user_'.$row['id'] .'" ></td>
		<td> <input type="submit" class="btn btn-info" value="Changer le mot de passe" name="pwd_' .$row['id'] .'"></td>
		<td><div class="checkbox"><input type="checkbox" name="del_' .$row['id'] .'"><input type="hidden" name="username_' .$row['id'] .'" value="' .$row['username'] .'"></div></td>
		</tr> 
		';
		}
		?>
	</tbody> 
</table>
<input type="submit" class="btn btn-info" value="Créer un utilisateur" name="add_user">
<input type="submit" class="btn btn-info" value="Supprimer les utilisateurs" name="delete_users">
</form> 	
</div>

</body>
</html>