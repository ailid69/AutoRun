<?php

/*-------------------------------------------------------------------------------------------------
		Télécharge un fichier de log contenu en base de données
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

	if (isset($_GET['id'])) 
   {
		$id = $_GET['id'];
		$myuser = $_SESSION['user']['id'];
		$result = get_logFile_with_content($db,$id,$myuser);
		
		/*-------------------------------------------------------------------------------------------------
			Si $result est vide c'est soit que l'id du fichier est incorrect 
			soit que l'utilisateur n'a pas le droit de télécharger ce fichier
			Dans ce cas on redirige vers la page d'accueil 
		-------------------------------------------------------------------------------------------------*/		
		
		if (empty($result)){
			header("Location: index.php?msg=5");
			die;
		}
		
		 header('Content-length: ' . $result['size']);
		 header('Content-type: ' . $result['type']);
		 header('Content-Disposition: attachment; filename='. $result['name']);
		 ob_clean();
		 flush();
		 echo $result['content'];
		 exit;
   }
   /*-------------------------------------------------------------------------------------------------
		Redirige vers la page d'accueil si aucun id de fichier n'est spécifié
	-------------------------------------------------------------------------------------------------*/		
   else{
		header("Location: index.php?msg=2");
		die("Redirecting to index.php");	 
   }
?>

