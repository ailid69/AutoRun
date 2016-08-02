<?php
 require_once 'config.php';
/*-------------------------------------------------------------------------------------------------
	Permet de vérifier dynamiquement dans un formulaire si un email est déjà utilisé
	Retourne un code HTTP 200 si le nom d'utilisateur est libre
	Retourne un code HTTP 400 si le nom d'utilisateur n'est pas libre
	Si le paramètre id est spécifié dans l'URL alors on exclu l'email de l'utilisateur de la recherche 
	(dans le cas ou l'utilisateur modifie ses propres informations, l'email existe déjà mais ce n'est pas un problème)
-------------------------------------------------------------------------------------------------*/		

if (!empty($_GET['email'])){
	$email = $db->quote($_GET['email']);
	if (!empty($_GET['id'])){	
		$query = "SELECT username FROM users WHERE id <> {$_GET['id']} AND email = {$email};";
	}
	else{
		$query = "SELECT username FROM users WHERE email = {$email};";
	}
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