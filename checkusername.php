<?php
 require_once 'config.php';

/*-------------------------------------------------------------------------------------------------
	Permet de vérifier dynamiquement dans un formulaire si un nom d'utilisateur est déjà utilisé
	Retourne un code HTTP 200 si le nom d'utilisateur est libre
	Retourne un code HTTP 400 si le nom d'utilisateur n'est pas libre
-------------------------------------------------------------------------------------------------*/		
 
if (!empty($_GET['username'])){

	$username = $db->quote($_GET['username']);
	$query = "SELECT username FROM users WHERE username = {$username};";

	try{ 
            $stmt = $db->prepare($query); 
            $stmt->execute(); 
			$result = $stmt->rowCount();
        } 
        catch(PDOException $ex){ die("Failed to run query: " . $ex->getMessage()); } 
        
    if($result == 0)
    {
        header("HTTP/1.1 200 OK");
    }
    else
    {
        header("HTTP/1.1 400 ALREADY USED");
    }
}
	else
{
   header("HTTP/1.1 500 ERROR");
}

?>