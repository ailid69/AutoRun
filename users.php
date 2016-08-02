<?php
	/*-------------------------------------------------------------------------------------------------
		Page pour la Gestion des utilisateurs (réservé aux admin)
		Liste les utilisateurs de l'applications
			- filtre / tri des colonnes de la table (plugin dataTables)
			- lien vers la page d'édition des informations utilisateurs
			- lien vers la page de modification du mot de passe
			- lien vers la page d'ajout d'utilisateurs
			- possibilité de supprimer des utilisateurs
	-------------------------------------------------------------------------------------------------*/		
    require_once 'config.php';
    
	/*-------------------------------------------------------------------------------------------------
		Redirige vers la page d'accueil si l'utilisateur n'est pas connecté 
		ou si l'utilisateur est connecté mais pas en tant qu'administrateur 
	-------------------------------------------------------------------------------------------------*/		
	
	if(empty($_SESSION['user'])||$_SESSION['user']['isadmin']!=1) 
    {
        header("Location: index.php?msg=1");
        die("Redirecting to index.php"); 
    }
	
	/*-------------------------------------------------------------------------------------------------
		Gestion des cas ou des données ont été postées 
	-------------------------------------------------------------------------------------------------*/		
	
	if(!empty($_POST)){  
	
		/*-------------------------------------------------------------------------------------------------
			Gestion du cas "Suppression d'utilisateurs" 
		-------------------------------------------------------------------------------------------------*/		
		
		if (!empty($_POST['delete_users'])){
			
			$usersToDel = "";
			$usersToDelDisplayName="";
			
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
			
			/*-------------------------------------------------------------------------------------------------			
				Si l'utilisateur clique sur "Suppression d'utilisateurs" mais qu'aucun utilisateur n'a été sélectionné
				On affiche un bandeau d'alerte 
			-------------------------------------------------------------------------------------------------*/		
			if ($emptyQuery==true){
				
				$alert='
			
				<div class="alert alert-danger" role="alert">
					<span class="glyphicon glyphicon-remove" aria-hidden="true" style="color:red"></span> Il faut sélectionner des utilisateurs avant de cliquer sur Supprimer des utilisateurs.
				</div>

				';
		
			}
			
			/*-------------------------------------------------------------------------------------------------			
				Si l'utilsateur clique sur "Suppression d'utilisateurs" et que un ou plusieurs utilisateurs ont été sélectionnés
				On affiche un bandeau de confirmation qui reprend le nom des utilisateurs à supprimer
				La liste des id utilisateurs à supprimer est envoyé en POST dans la variable 'usersToDel'
			-------------------------------------------------------------------------------------------------*/		
			
			else
			{
				$usersToDel = substr($usersToDel,0,strlen($usersToDel)-1);
				$usersToDelDisplayName = substr($usersToDelDisplayName,0,strlen($usersToDelDisplayName)-1);
				$alert= '
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
		
		/*-------------------------------------------------------------------------------------------------			
			L'utilisateur a confirmé la suppression d'une liste d'utilisateurs 
		-------------------------------------------------------------------------------------------------*/		
		
		elseif (!empty($_POST['deleteUsersOK']))
		{
			$query = "DELETE FROM users WHERE id IN (".$_POST['usersToDel'] ." )"; 
			
			try{ 
				$stmt = $db->prepare($query); 
				$result = $stmt->execute(); 
			} 
			catch(PDOException $ex){ die("Failed to run query: " .$query .'.' . $ex->getMessage()); } 
			$alert = ' 
			<div class="alert alert-success" role="alert">
					<span class="glyphicon glyphicon glyphicon-ok" aria-hidden="true"></span> Le(s) utilisateur(s) suivant(s) ont bien été supprimé(s): <strong> ' . $_POST['usersToDelDisplayName'] .'</strong> 
			</div>
			';
		}
		
		/*-------------------------------------------------------------------------------------------------			
			Si l'utilisateur ne confirme pas la suppression des utilisateurs, il est redirigé vers la page "users.php"
		-------------------------------------------------------------------------------------------------*/		
		elseif (!empty($_POST['deleteUsersCancel']))
		{
			header("Location: users.php");
		}
		
		/*-------------------------------------------------------------------------------------------------			
			Si l'utilisateur a cliqué sur "Ajouter des utilisateurs"
			il est redirigé vers la page "adduser.php"
		-------------------------------------------------------------------------------------------------*/		
		elseif (!empty($_POST['add_user']))
		{
			header("Location: adduser.php");
		}
		else
		/*-------------------------------------------------------------------------------------------------			
			Si des données ont été postées et que l'on n'est dans aucun des cas précédents
			Alors l'utilisateur a cliqué sur "Editer" ou "Changer le mot de passe"
		-------------------------------------------------------------------------------------------------*/			
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

/*-------------------------------------------------------------------------------------------------			
	Récupération de l'ensemble des utilisateurs présents en base pour affichage dans un tableau
-------------------------------------------------------------------------------------------------*/				
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
            $result = $stmt->execute(); 
        } 
        catch(PDOException $ex){ die("Failed to run query: " . $ex->getMessage()); } 
	
	?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>AutoRun - Gestion des utilisateurs</title>
    <meta name="description" content="My EDF application for Packages">
    <meta name="author" content="david.aili@hpe.com">

    <link href="/css/bootstrap.min.css" rel="stylesheet" media="screen">
	<link href="/css/dataTables.bootstrap.min.css" rel="stylesheet" media="screen">
	<link href="/css/jquery.dataTables.min.css" rel="stylesheet" media="screen">
	<style type="text/css">
        .hero-unit { background-color: #fff; }
        .center { display: block; margin: 0 auto; }
		 body { padding-top: 70px; }
    </style>
</head>
<body>
	<script src="/js/jquery.min.js"></script>
	<script src="/js/bootstrap.min.js"></script>
	<script src="/js/dataTables.bootstrap.js"></script>
	<script src="/js/jquery.dataTables.min.js"></script>
	
	<!-- -------------------------------------------------------------------------------------------------------------------
		Initialisation de la table user : modification des messages pour affichage en francais
		Les 3 dernières colonnes ne sont pas triable (ne contient pas d'information mais des boutons ou cases à cocher )
	-------------------------------------------------------------------------------------------------------------------  -->
	
	<script type="text/javascript">
			$(document).ready(function() {
				$('#users').DataTable( {


	language: {
        processing:     "Traitement en cours...",
        search:         "Rechercher&nbsp;:",
        lengthMenu:    "Afficher _MENU_ &eacute;l&eacute;ments",
        info:           "Affichage de l'&eacute;lement _START_ &agrave; _END_ sur _TOTAL_ &eacute;l&eacute;ments",
        infoEmpty:      "Affichage de l'&eacute;lement 0 &agrave; 0 sur 0 &eacute;l&eacute;ments",
        infoFiltered:   "(filtr&eacute; de _MAX_ &eacute;l&eacute;ments au total)",
        infoPostFix:    "",
        loadingRecords: "Chargement en cours...",
        zeroRecords:    "Aucun &eacute;l&eacute;ment &agrave; afficher",
        emptyTable:     "Aucune donnée disponible dans le tableau",
        paginate: {
            first:      "Premier",
            previous:   "Pr&eacute;c&eacute;dent",
            next:       "Suivant",
            last:       "Dernier"
        },
        aria: {
            sortAscending:  ": activer pour trier la colonne par ordre croissant",
            sortDescending: ": activer pour trier la colonne par ordre décroissant"
        }
    },
	
	"columns": [
    null,
    null,
    null,
    null,
    null,
	{ "orderable": false },
	{ "orderable": false },
	{ "orderable": false }
  ]
} );

			} )

</script>

<?php include('myNavBar.php'); ?>

<div class="container hero-unit">
	<div class="panel panel-default">

  <div class="panel-heading">
   <p><h2>Gestion des utilisateurs</h2></p>
  </div>
  <div class="panel-body">

<?php
	if (isset($alert)){
		echo $alert;
	}
?>
 <!-- Table -->
  <form action="users.php" method="post"> 
  <table id="users" class="table" cellspacing="0" width="100%">
    <thead> 
		<tr align="center"> 
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
			<td align="center">'
		;
			if($row['isadmin'])	{echo '<div class="hidden">1</div><span class="glyphicon glyphicon glyphicon-ok" aria-hidden="true" style="color:green"></span>';}
			else				{echo '<div class="hidden">0</div>';} 
		echo '		
		</td> 
		<td align="center"> <input type="submit" class="btn btn-info" value="Editer" name="edit_user_'.$row['id'] .'" ></td>
		<td align="center"> <input type="submit" class="btn btn-info" value="Changer le mot de passe" name="pwd_' .$row['id'] .'"></td>
		<td align="center"><input type="checkbox" name="del_' .$row['id'] .'"><input type="hidden" name="username_' .$row['id'] .'" value="' .$row['username'] .'"></td>
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

<script type="text/javascript">
	$('#users').removeClass( 'display' );
	$('#users').addClass('table table-striped table-bordered');
</script>
</body>
</html>
