<?php 
/* ------------------------- SECTION A PARAMETRER EN FONCTION DE L'ENVIRONNEMENT ----------------------- */
	/* Variables de connexion à la base mySQL */
		define("USERNAME","webuser");
		define("PASSWORD","webuser");
		define("HOST","localhost");
		define("DBNAME","EDF");
	/* Nom des tables dans la base mySQL */
		define("DB_PACKAGETABLE","packages");
		define("DB_PACKAGEHISTORYTABLE" ,"packages_history");
	/* chemin vers le répertoire de dépot des packages (l'utilisateur apache doit avoir les droits en écriture)*/
		define("UPLOAD_DIR","/home/ailid/PACKAGE_REPO/");
	/* Expression régulière pour filtrer les fichier à télécharger */
		define("ACCEPT_FILE_TYPES","/.(zip)$/i");
	/* Statut à écrire en base quand la phase d'upload est en succès */
		define("MSG_UPLOAD_OK","Le package est bien téléchargé, en attente de contrôle");
	/* Mot de passe pour lire les archives */
		//define ("ZIP_PASSWORD","AutoRun");
	/* Sépateur  entre deux parmètres dans la section commentaire des archives .zip */
		define("ZIPCOMMENT_SEPARATOR","\r\n");
	/* Sépateur  entre un paramètre et sa valeur dans la section commentaire des archives .zip */
		define ("ZIPCOMMENT_SEPARATOR_PARAMVAL","=");
	/* Fichier de log - Attention l'utilisateur apache doit avoir les droits sur le répertoire contenant le fichier
		Le fichier spécifié sera suffixé par la date du jour au jormat YYYYMMDD puis .log
	*/
		define ("LOGFILE","/var/log/apache2/autorun");
/* ------------------------- SECTION A PARAMETRE EN FONCTION DE L'ENVIRONNEMENT ----------------------- */	
	
	ini_set("display_errors", 1);
	
	$db;
    $options = array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'); 
    try { 
		//$db = new PDO("mysql:host={::HOST};dbname={::DBNAME};charset=utf8", ::USERNAME, ::PASSWORD, $options); 
		$db = new PDO('mysql:host='.HOST.';dbname='.DBNAME.';charset=utf8', USERNAME, PASSWORD, $options); 
	} 
	catch(PDOException $ex){ 
		die("Failed to connect to the database: " . $ex->getMessage());
	} 
	
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); 
	
		session_start(); // ready to go!
		$now = time();
		if (isset($_SESSION['discard_after']) && $now > $_SESSION['discard_after']) {
		// this session has worn out its welcome; kill it and start a brand new one
			session_unset();
			session_destroy();
			session_start();
		}

// either new or old, it should live at most for another hour
		$_SESSION['discard_after'] = $now + 3600;

//header('Content-Type: text/html; charset=utf-8'); 

//		echo ('<BR>' . session_status());
//		echo ('<BR>' . session_id());
		
	function show_last_status_by_package($mydb,$user,$isadmin){
	
		$query = 'SELECT '
		.'p.id,p.name,p.upload_date,p.archive,p.autorun,p.package,p.created,p.project,p.server,p.user,p.comment,p.size,p.autorun,'
		.'laststatus.state,laststatus.substate,laststatus.comment,laststatus.date,'
		.'u.username
					FROM packages p  
					LEFT JOIN
						(SELECT p.* FROM packages_history p 
							INNER JOIN
								(SELECT package_id, MAX(date) AS maxdate
									FROM packages_history
									GROUP BY package_id
								) p2 
							ON p.package_id = p2.package_id AND p.date = p2.maxdate
						) laststatus
					ON laststatus.package_id = p.id
					LEFT JOIN users u on u.id = p.uploaded_by';
		if ($isadmin != 1) {
			$query = $query . ' WHERE p.uploaded_by = "' .$user .'"';
		} 
		
		$query = $query . ' ORDER BY laststatus.date DESC';
					
		  try {  
			 $stmt = $mydb->prepare($query); 
            $result = $stmt->execute(); 
			
			
        } 
        catch(PDOException $ex){ die("Failed to run query: " . $ex->getMessage()); } 
		return $stmt->fetchAll();
	}
	
	function get_package_detail($mydb,$packid){
	
		$query = '
		SELECT 
			p.id, p.name,p.upload_date,p.archive,p.autorun,p.package,p.created,p.project,p.server,p.user,p.comment,
			u.username
			from packages p LEFT JOIN users u on u.id = p.uploaded_by
			WHERE p.id="'.$packid.'"';
	
		  try {  
			$stmt = $mydb->prepare($query); 
            $result = $stmt->execute(); 
	
        } 
        catch(PDOException $ex){ die("Failed to run query: " . $ex->getMessage()); } 
		return $stmt->fetchAll();
	}
	
	function get_package_history($mydb,$packid){
	
		$query = '
		SELECT 
			state, substate, comment,date,id
		FROM packages_history
		WHERE package_id = "' . $packid . '" ORDER BY date DESC
		';			
		  try {  
			$stmt = $mydb->prepare($query); 
            $result = $stmt->execute(); 
	
        } 
        catch(PDOException $ex){ die("Failed to run query: " . $ex->getMessage()); } 
		return $stmt->fetchAll();
	}
	
	function get_logFiles($mydb,$pack_hist_id){
		$query = '
		SELECT 
			id, name, type,size
		FROM log_files
		WHERE id_packages_history = "' . $pack_hist_id . '"
		';			
		  try {  
			$stmt = $mydb->prepare($query); 
            $result = $stmt->execute(); 
	
        } 
        catch(PDOException $ex){ die("Failed to run query: " . $ex->getMessage()); } 
		return $stmt->fetchAll();
	}
	
	function get_logFile_with_content($mydb,$fileid,$myuser){
			$query = 'SELECT isadmin FROM users WHERE id = "'. $myuser . '"';
		try {  
			$stmt = $mydb->prepare($query); 
            $result = $stmt->execute(); 
        } 
        catch(PDOException $ex){ die("Failed to run query: " . $ex->getMessage()); } 
		if ($stmt->rowCount()!=1) {die;}
		$isadmin = $result[0]['isadmin'];
		if ($isadmin == 1){
			$query = 'SELECT l.id,l.name,l.type,l.size,l.content FROM log_files l WHERE id = "' . $fileid . '"';
		}
		else{
			$query = '
				SELECT l.id,l.name,l.type,l.size,l.content
				FROM log_files l 
					INNER JOIN packages_history h 
						ON h.id = l.id_packages_history 
					INNER JOIN packages p 
						ON p.id = h.package_id
					INNER JOIN users u 
						ON u.id = p.uploaded_by
				WHERE l.id = "' . $fileid . '" AND u.id = "'.$myuser.'"
				';			
		}
		  try {  
			$stmt = $mydb->prepare($query); 
            $result = $stmt->execute(); 
        } 
        catch(PDOException $ex){ die("Failed to run query: " . $ex->getMessage()); } 
		return $stmt->fetchAll();
	}

	function isUserAllowedToViewPackage($mydb,$pack){

		$query = 'SELECT uploaded_by FROM packages WHERE id="'.$pack.'"';
		  try {  
			$stmt = $mydb->prepare($query); 
            $result = $stmt->execute(); 
        } 
        catch(PDOException $ex){ die("Failed to run query: " . $ex->getMessage()); } 

		if ($stmt->rowCount()!=1)
		{
			return false;
		}
		else 
		{
			$res = $stmt->fetch();
			if ($res['uploaded_by'] == $_SESSION['user']['id']){
				return true;	
			}
			else {
				return false;
			}
		}
	}
	
	function displayErrorMessage($errCode){
		switch ($errCode){
			case 1 : $msg = "Vous devez être connecté en tant qu'administrateur pour accéder à cette partie de l'application.";break;
			case 2 : $msg = "Il manque un paramètre dans l'URL ... ";break;
			case 3 : $msg = "Pour changer un mot de passe il faut être connecté et seul les administrateurs peuvent modifier les mots de passe des autres utilisateurs.";break;
			case 4 : $msg = "Vous devez être connecté pour accéder à cette partie de l'application.";break;
			case 5 : $msg = "Vous avez tenté de télécharger un fichier qui n'existe pas ou sur lequel vous n\'avez pas de droits.";break;
			case 6 : $msg = "Problème d'authentification.<BR>Merci de vérifier votre nom d\'utilisateur et mot de passe.";break;
			case 7 : $msg = "Vous avez tenté d'éditer les informations d'un autre utiliseateur alors que vous n'êtes pas administrateur!";break;
			case 8 : $msg = "Vous avez tenté d'obtenir des informations sur un package que vous n'avez pas chargé alors que vous n'êtes pas administrateur!";break;
			
		
			default : 	$msg = "";break;
		 }			
		return $msg;
	}
	
	function isNewPackage($mydb,$package){
		$query ='SELECT COUNT(id) as nb FROM packages WHERE package="'.$package.'"';
		 try {  
			$stmt = $mydb->prepare($query); 
            $result = $stmt->execute(); 
			$res = $stmt->fetch();
			} 
        catch(PDOException $ex){ die("Failed to run query: " . $ex->getMessage()); } 
		//print_r($res);
		if ($res['nb']==0){
			return true;
		}
		else{
			return false;	
		}
	}
	
	function displayInfoMessage($errCode){
		switch ($errCode){
			case 1 : $msg = 'Bonjour <strong>'.$_SESSION['user']['username'].'</strong>.<BR>Vous êtes connecté en tant qu\'administrateur...';break;
			case 2 : $msg = 'Bonjour <strong>'.$_SESSION['user']['username'].'</strong>.<BR>Vous êtes connecté en tant qu\'utilisateur...';break;
			case 3 : $msg = 'Vous avez été correctement deconnecté';break;
			default : 	$msg = "";break;
		 }			
		return $msg;
	}
	
	function sizetohumanreadable($size){
		$mysize = intval($size);
		if ($mysize==0 || empty($mysize)){
			return null;
		}
		if ($mysize < 1024){
			$mysize = strval($mysize) . ' o';
			return $mysize;
		}
		if ($size < (1024*1024)){
			$mysize = round($mysize/1024,2) . " Ko";
			return $mysize;
		}
		if ($size<(1024*1024*1024)){
			$mysize = round($mysize/1024/1024,2) . " Mo";
			return $mysize;
		}
	}
	
	function writeLog($msg){
		//Something to write to txt log
		$log  = "User: ".$_SERVER['REMOTE_ADDR'].' - '.date("F j, Y, g:i a"). '------------------------- : '.$msg . PHP_EOL;
		//Save string to log, use FILE_APPEND to append.
		file_put_contents(LOGFILE . date("Ymj") . '.log', $log, FILE_APPEND);
	}
	
?>