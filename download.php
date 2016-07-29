<?php

    require_once 'config.php';
	print_r($_SESSION);
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

		if (empty($result)){
			header("Location: index.php?msg=5");
			die;
		}
		$result = $result[0];
		writeLog('--- Téléchargement de fichier ---');
		writeLog($result);
		 header('Content-length: ' . $result['size']);
		 header('Content-type: ' . $result['type']);
		 header('Content-Disposition: attachment; filename='. $result['name']);
		 ob_clean();
		 flush();
		 echo $result['content'];
		 exit;
   }
   else{
		header("Location: index.php?msg=2");
		die("Redirecting to index.php");	 
   }
?>

