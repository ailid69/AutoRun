<?php
	/*-------------------------------------------------------------------------------------------------
		Page pour visualiser le contenu d'un fichier de log
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
		Redirige vers la page d'accueil si l'id du fichier n'est pas spécifié
	-------------------------------------------------------------------------------------------------*/	
	if (empty($_GET['fileid'])){
			header("Location: index.php?msg=2");
			die("Redirecting to index.php");
	}
	
	$fileid=$_GET['fileid'];
	
	/*-------------------------------------------------------------------------------------------------
		Redirige vers la page d'accueil si l'utilisateur n'est pas administrateur et qu'il n'est pas authorisé à voir ce fichier
	-------------------------------------------------------------------------------------------------*/	
	if ($_SESSION['user']['isadmin']!=1 && isUserAllowedToViewFile($db,$fileid)==false){
			header("Location: index.php?msg=8");
			die("Redirecting to index.php");
	}
	else{
		$myuser = $_SESSION['user']['id'];
		$result = get_logFile_with_content($db,$fileid,$myuser);
		if (empty($result)){
			header("Location: index.php?msg=2");
			die("Redirecting to index.php");
		}
	}

?>
	

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>AutoRun - Visualiser le contenu d'un fichier de log <?php echo $result['name'] ?></title>
    <meta name="description" content="My EDF application for Packages">
    <meta name="author" content="david.aili@hpe.com">

    <link href="/css/bootstrap.min.css" rel="stylesheet" media="screen">
	<link href="/css/dataTables.bootstrap.min.css" rel="stylesheet" media="screen">
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
			<p><h2>Contenu d'un fichier (<?php echo $result['name'] ?>)</h2></p>
		</div>
		<div class="panel-body" style="max-height: 400px;overflow-y: scroll;">
<?php 
	if (isset($msg)){
		echo ' <div class="alert alert-info" role="alert">'.$msg.'</div>';
	}
	if (isset($errmsg)){
		echo ' <div class="alert alert-danger" role="alert">'.$errmsg.'</div>';
		echo '</div></div></div></body></html>';
	}
?>
		<code>
<?php 
$text = nl2br($result['content']);
$text = str_replace("\t", '&nbsp;&nbsp;&nbsp;&nbsp;', $text); 
$text = str_replace("[71G", '&nbsp;&nbsp;&nbsp;&nbsp;', $text); 
echo $text
?>
		</code>
		</div>
	<div class="panel-footer">
<?php	
	echo '	<a href="download.php?id=' . $fileid. '" class="btn btn-info">Télécharger</a>';
	echo '	<a href="packages_history.php?viewHistory='. $result['package'].'" class="btn btn-info">Retour</a>&nbsp';
	

?>
	</div>
	</div>
	
</div>
</body>
</html>