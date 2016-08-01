<?php
	/*-------------------------------------------------------------------------------------------------
		Page pour visualiser le détail des opétations effectuées sur un package
		L'admnistrateur a accès aux fonctions d'ajout et de suppression de fichiers de log
		Il peut y avoir un ou plusieurs fichiers de log attachés à un chaque étape du cycle de vie d'un package
	-------------------------------------------------------------------------------------------------*/	
    require_once 'config.php';
   
	/*-------------------------------------------------------------------------------------------------
		Redirige vers la page d'accueil si l'utilisateur n'est pas connecté 
	-------------------------------------------------------------------------------------------------*/		
   if(empty($_SESSION['user']))
    {
        header("Location: index.php?msg=4");
        die("Redirecting to index.php"); 
    }
	/*-------------------------------------------------------------------------------------------------
		Redirige vers la page d'accueil si l'id du package n'est pas spécifié
	-------------------------------------------------------------------------------------------------*/	
	if (empty($_GET['viewHistory'])){
			header("Location: index.php?msg=2");
			die("Redirecting to index.php");
	}
	
	$packid=$_GET['viewHistory'];
	
	/*-------------------------------------------------------------------------------------------------
		Redirige vers la page d'accueil si l'utilisateur n'est pas administrateur et qu'il n'est pas authorisé à voir ce package
	-------------------------------------------------------------------------------------------------*/	
	if ($_SESSION['user']['isadmin']!=1 && isUserAllowedToViewPackage($db,$packid)==false){
			header("Location: index.php?msg=8");
			die("Redirecting to index.php");
	}

	else {
	/*-------------------------------------------------------------------------------------------------
		$package contient les informations relatives au package
		$history continet les informations relative aux différents états du package dans le temps
	-------------------------------------------------------------------------------------------------*/	
		
		$package = get_package_detail($db,$packid);
		$history = get_package_history($db,$packid);	
		
		/*-------------------------------------------------------------------------------------------------
			Gestion des données envoyés en POST 
			Il s'agit de l'ajout ou de la suppression de fichier de log
		-------------------------------------------------------------------------------------------------*/
		if(!empty($_POST)){  
		
		$upload = false;
		$delete = false;

		$array = array_keys($_POST);
		foreach ($array as $param)
		/*-------------------------------------------------------------------------------------------------
			On parcourt l'ensemble des variables envoyées dans les POST
			Si l'une d'elles commence par delete c'est que l'utilisateur a cliqué sur "Supprimer un fichier de log"
			Le fin du nom de la variable permet de connaitre l'id du fichier à supprimer
		-------------------------------------------------------------------------------------------------*/
		{
			if (substr($param,0,6)=="delete"){
				
			$id_file =  explode("_", $param)[1];
				$delete = true;
			}
		}
		
		/*-------------------------------------------------------------------------------------------------
			On parcourt l'ensemble des variables envoyées dans les POST
			Pour savoir si l'utilisateur a cliqué sur "Charger un fichier de log"
			Si c'est le cas on extrait l'id package history du nom du bouton
		-------------------------------------------------------------------------------------------------*/
		$array = array_keys($_FILES);
		foreach ($array as $param){
			
				if ($_FILES[$param]['name']!=""){
					$id_packages_history = explode("_",$param)[1];
					$upload = true;
				}	
		} 
	
		if($upload == true && $_FILES['userfile_'.$id_packages_history]['size'] > 0) {
			/*-------------------------------------------------------------------------------------------------
				Procédure pour stocker le fichier téléchargé en base
			-------------------------------------------------------------------------------------------------*/
			$fileName = $_FILES['userfile_'.$id_packages_history]['name'];
			$tmpName  = $_FILES['userfile_'.$id_packages_history]['tmp_name'];
			$fileSize = $_FILES['userfile_'.$id_packages_history]['size'];
			$fileType = $_FILES['userfile_'.$id_packages_history]['type'];

			$fp      = fopen($tmpName, 'r');
			$content = fread($fp, filesize($tmpName));
			$content = addslashes($content);
			fclose($fp);

			if(!get_magic_quotes_gpc())
			{
				$fileName = addslashes($fileName);
			}
			/*-------------------------------------------------------------------------------------------------
				Requête pour insérer le fichier en base
			-------------------------------------------------------------------------------------------------*/
			$query = "INSERT INTO log_files (name, size, type, content,id_packages_history ) ".
			"VALUES ('$fileName', '$fileSize', '$fileType', '$content', '$id_packages_history')";

			try {  
				$stmt = $db->prepare($query); 
				$result = $stmt->execute();
				$msg = 'Le fichier de log <strong>' . $fileName . ' </strong> a bien été inséré.';
					
				} 
			catch(PDOException $ex){ 
				$errmsg = 'Il y a eu un problême avec l\'insertion du fichier de log <strong>' . $fileName .'</strong>';
			} 
		}
		else if ($delete == true){
			/*-------------------------------------------------------------------------------------------------
				Procédure pour supprimer un fichier de log
			-------------------------------------------------------------------------------------------------*/
			$query = "DELETE FROM log_files WHERE id='$id_file'";

			try {  
				$stmt = $db->prepare($query); 
				$result = $stmt->execute(); 
				$msg = 'Le fichier de log <strong>' . $fileName . '</strong> a bien été supprimé.';
				} 
			catch(PDOException $ex){ 
				$errmsg = 'Il y a eu un problême avec la suppression du fichier de log <strong>' . $fileName . '</strong>';
	
			}
		}
	}	
	
	}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>AutoRun - Cycle de vie d'un package</title>
    <meta name="description" content="My EDF application for Packages">
    <meta name="author" content="david.aili@hpe.com">

    <link href="/css/bootstrap.min.css" rel="stylesheet" media="screen">
	<link href="/css/dataTables.bootstrap.min.css" rel="stylesheet" media="screen">
	<link href="/css/jquery.dataTables.min.css" rel="stylesheet" media="screen">
	<link rel="stylesheet" href="/css/jquery.fileupload.css">
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

<form enctype="multipart/form-data" role="form" action="packages_history.php?viewHistory=<?php echo $packid;?>" method="post">

<div class="container hero-unit">
	<div class="panel panel-default">
		<div class="panel-heading">
			<p><h2>Cycle de vie d'un package</h2></p>
		</div>
	<div class="panel-body">


<?php 
	if (isset($msg)){
		echo ' <div class="alert alert-info" role="alert">'.$msg.'</div>';
	}
	if (isset($errmsg)){
		echo ' <div class="alert alert-danger" role="alert">'.$errmsg.'</div>';
	}
?>


	<div class="panel panel-primary">
	<div class="panel-heading">
		<p><h3>Détails du package</h3></p>
	</div>
	<div class="panel-body">
	
	<H4>
	<div class="row"> 
			<div class="col-md-2">Nom du package</div>
			<div class="col-md-2">Nom du fichier</div>
			<div class="col-md-2">Créé le</div>
			<div class="col-md-2">Téléchargé par</div> 
			<div class="col-md-2">Téléchargé le</div>
			<div class="col-md-2">Créé à partir de</div>
	</div>
	</h4>	
	<?php 
	echo '
	<div class="row"> 
			<div class="col-md-2">'. $package['package'] .'</div>
			<div class="col-md-2">'. $package['name'] .'</div>
			<div class="col-md-2">'. $package['created'] .'</div>
			<div class="col-md-2">'. $package['username'] .'</div> 
			<div class="col-md-2">'. $package['upload_date'] .'</div>
			<div class="col-md-2">'. $package['autorun'] .'</div>
	</div> 
	';
	?>
	<H4>
	<div class="row"> 
			<div class="col-md-2">Nom de l'archive</div>
			<div class="col-md-2">Projet</div>
			<div class="col-md-2">Serveur</div>
			<div class="col-md-2">Utilisateur</div>
			<div class="col-md-2">Commentaires</div>
	</div>
	</H4>
	<?php 
	echo '
	<div class="row"> 
			<div class="col-md-2">'. $package['archive'] .'</div>
			<div class="col-md-2">'. $package['project'] .'</div>
			<div class="col-md-2">'. $package['server'] .'</div>
			<div class="col-md-2">'. $package['user'] .'</div>
			<div class="col-md-4">'. $package['comment'] .'</div>
	</div> 
	';
	?>
	 </div>
	 </div>
	 
	<div class="panel panel-primary">
		<div class="panel-heading">
			<p><h3>Historique </h3></p>
		</div>
		<div class="panel-body">
	 <table id="history" class="table" cellspacing="0" width="100%">
    
	<thead> 
		<tr> 
			<th>Phase</th>
			<th>Statut</th>
			<th>Commentaires</th>
			<th>Date</th>
			<th>Fichier(s) de log</th> 
			<th></th>
		</tr> 
	</thead> 
	<tbody> 
	
	<?php 
	 foreach ($history as $row){
		 switch ($row['substate']){
				case "OK": $mysublabel = "label label-success";break;
				case "ERROR" : $mysublabel = "label label-danger";break;
				default : 	$mysublabel = "label label-default";break;
		 	}
			 switch ($row['state']){
				case "UPLOAD" : $mylabel = "label label-warning";break;
				case "CONTROL" : $mylabel = "label label-primary";break;
				case "HANDLE" : $mylabel = "label label-info";break;
				case "IMPORT" : $mylabel = "label label-default";break;
				case "EXECUTE" : $mylabel = "label label-success";break;
				default : 	$mylabel = "label label-danger";break;
		   }
		 echo '
		<tr>
			<td><span class="label '. $mylabel .'">' . $row['state'] .'</span></td> 
			<td> <span class="label '. $mysublabel .'">' . $row['substate'] .'</span></td>
			<td>' . $row['comment'] . '</td>
			<td>' . $row['date'] . '</td>
		';
		
		$result = get_logFiles($db,$row['id']);
		echo '<td>';
		foreach($result as $file){
			echo 	'<div class="row">' ;
				echo	'<span style="font-size:10px;"> 
							<div class="col-md-8">
								<a href="download.php?id='.$file['id'].'">'. $file['name'] .'</a>
							</div>
							<!--div class="col-md-5">'. $file['type'] .'</div>
							<div class="col-md-2">'. $file['size'] .'</div-->
						</span>';
				if($_SESSION['user']['isadmin']==1){
					echo'
						<div class="col-md-4">
							<input type="submit" class="btn btn-info btn-xs" name="delete_'.$file['id'].'" value="Supprimer" id="delete_'.$file['id'].'"></input>
						</div>	
					</div>
					';
				}
		}
		echo '</td>';
		if($_SESSION['user']['isadmin']==1){	
			echo '
			<td>	
					
							 <!-- The fileinput-button span is used to style the file input field as button -->
							<span class="btn btn-info btn-xs fileinput-button">
								<input type="file" name="userfile_'.$row['id'].'" onchange="this.form.submit()">Charger un fichier de log </input>		
								<input type="hidden" name="MAX_FILE_SIZE" value="16777216"></input>
							</span>
					
			</td>
		</tr>
		';
		}
	 }
	 ?>
	</tbody>
	</table>
</div>
</div>
</div>
</div>
</form>

<script type="text/javascript">
$(document).ready(function(){
    $('[data-toggle="tooltip"]').tooltip(); 
});
</script>

</body>
</html>