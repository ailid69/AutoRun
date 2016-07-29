<?php
 require_once 'config.php';
/* check if email address is already registered */

if (!empty($_GET['email'])){

    //$username = $db->$_GET['userName'];
	$email = $db->quote($_GET['email']);
	
	if (!empty($_GET['id'])){
		
		$query = "SELECT username FROM users WHERE id <> {$_GET['id']} AND email = {$email};";
	}
	else{
		$query = "SELECT username FROM users WHERE email = {$email};";
	}
	printf($query);
	try{ 
            $stmt = $db->prepare($query); 
            $stmt->execute($query_params); 
			$result = $stmt->rowCount();
        } 
        catch(PDOException $ex){ die("Failed to run query: " . $ex->getMessage()); } 
        
   printf("rows returned:" . $result);
    if($result == 0)
    {
        header("HTTP/1.1 200 OK");
		printf("email free");
    }
    else
    {
        header("HTTP/1.1 400 ALREADY USED");
		printf("email already used!");
    }
}
	else
{
   header("HTTP/1.1 500 ERROR");
}

?>