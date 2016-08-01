<?php 

/*-------------------------------------------------------------------------------------------------
		Page pour vérifier le login / mot de passe d'un utilisateur
		Redirige vers la page d'accueil avec un message d'erreur ou de succès
	-------------------------------------------------------------------------------------------------*/		

	require_once 'config.php'; 

    $submitted_username = ''; 
    if(!empty($_POST['username'])){ 
        $query = ' 
            SELECT 
                id, 
                username, 
                password, 
                salt,
				isadmin
            FROM users 
            WHERE 
                username = "' . $_POST['username'] . '";';
        
        try{ 
            $stmt = $db->prepare($query); 
            $result = $stmt->execute(); 
        } 
        catch(PDOException $ex){ 
			die("Failed to run query: " . $ex->getMessage()); 
		} 
        
		/*-------------------------------------------------------------------------------------------------
			Si la requête ne retourne aucun enregistrement c'est que l'utilisateur n'existe pas
			Dans ce cas on redirige vers la page d'accueil
		-------------------------------------------------------------------------------------------------*/	
		if ($stmt->rowCount()!=1){
			header("Location: index.php?msg=9");
			die;
		}
		
		$login_ok = false;
		$isadmin = false;	
		
        $row = $stmt->fetch(); 
        if($row){ 
		/*-------------------------------------------------------------------------------------------------
			Vérification du mot de passe 
		-------------------------------------------------------------------------------------------------*/	
            $check_password = hash('sha256', $_POST['password'] . $row['salt']); 
            for($round = 0; $round < 65536; $round++){
                $check_password = hash('sha256', $check_password . $row['salt']);
            } 
            if($check_password === $row['password']){
                $login_ok = true;
				unset($row['salt']); 
				unset($row['password']); 
		/*-------------------------------------------------------------------------------------------------
			Le mot de passe saisi correspond au mot de passe en base.
			On ajoute le user id et isadmin dans la session utilisateur
			Puis on redirige vers la page d'accueil avec un message d'information (différent selon le role de l'utilisateur)
		-------------------------------------------------------------------------------------------------*/	
				$_SESSION['user'] = $row;  
				if($row['isadmin'] == 1){
					$isadmin=true;
					header("Location: index.php?info=1");
					die;
				}
				else{
					header("Location: index.php?info=2");
					die;	
				}
            }
			/*-------------------------------------------------------------------------------------------------
			Le mot de passe saisi ne correspond pas au mot de passe en base.
			On redirige vers la page d'accueil avec un message d'erreur
		-------------------------------------------------------------------------------------------------*/	
			else{
				header("Location: index.php?msg=6");
				die;
			}
			
        } 
    } 
	/*-------------------------------------------------------------------------------------------------
		Il manque des varialbes dans le POST
		On redirige vers la page d'accueil avec un message d'erreur
	-------------------------------------------------------------------------------------------------*/	
	else{
		header("Location: index.php?msg=2");
		die;
	}
?> 
