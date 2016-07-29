<?php
 require_once 'config.php';
/* check if username is already registered */

if (!empty($_GET['username'])){

    //$username = $db->$_GET['userName'];
	$username = $db->quote($_GET['username']);
	//$query = "SELECT username FROM users WHERE username = '{$username}' LIMIT 1;";
	$query = "SELECT username FROM users WHERE username = {$username};";
   //printf($query);
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
		printf("username free");
    }
    else
    {
        header("HTTP/1.1 400 ALREADY USED");
		printf("username already used!");
    }
}
	else
{
   header("HTTP/1.1 500 ERROR");
}

?>