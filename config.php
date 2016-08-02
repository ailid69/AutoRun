<?php 

/*-------------------------------------------------------------------------------------------------
	SECTION A PARAMETRER EN FONCTION DE L'ENVIRONNEMENT 
	Ce fichier  : 
		-sert à créer le lien vers la base de données
		-sert à stocker des variables de l'application
		-contient des fonctions utilisées par d'autres parties de l'application
-------------------------------------------------------------------------------------------------*/

	/* ini_set("display_errors", 1); doit être commenté en PRODUCTION */
	ini_set("display_errors", 1);
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
		//define("UPLOAD_DIR","C:/temp/EDF/");
	/* Expression régulière pour filtrer les fichier à télécharger */
		define("ACCEPT_FILE_TYPES","/.(zip)$/i");
	/* Statut à écrire en base quand la phase d'upload est en succès */
		define("MSG_UPLOAD_OK","Le package est bien téléchargé, en attente de contrôle");
	/* Mot de passe pour lire les archives -- Pas nécessaire car on ne fait que lire les commentaires de l'archive*/
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
	
	/* $db permet d'accèder à la base de données*/
	$db;
    $options = array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'); 
    try { 
		$db = new PDO('mysql:host='.HOST.';dbname='.DBNAME.';charset=utf8', USERNAME, PASSWORD, $options); 
	} 
	catch(PDOException $ex){ 
		die("Failed to connect to the database: " . $ex->getMessage());
	} 
	
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); 
	
		session_start(); // Démarrage de la session php 
		$now = time();
		if (isset($_SESSION['discard_after']) && $now > $_SESSION['discard_after']) {
		//La session a expirée, on en crée une nouvelle
			session_unset();
			session_destroy();
			session_start();
		}

// La durée de vie de la session est étendue d'une heure
		$_SESSION['discard_after'] = $now + 3600;


	/* ---------------------------------------------------------------------------------------
		Retourne un tableau contenant pour chaque package, des informations sur ce package ainsi que sur son dernier état
	---------------------------------------------------------------------------------------- */		
	function show_last_status_by_package($mydb,$user,$isadmin){
	
		$query = 'SELECT '
		.'p.name,p.upload_date,p.autorun,p.package,p.created,p.project,p.server,p.user,p.comment,p.size,p.autorun,'
		.'laststatus.state,laststatus.substate,laststatus.comment,laststatus.date,'
		.'u.username
					FROM packages p  
					LEFT JOIN
						(SELECT p.* FROM packages_history p 
							INNER JOIN
								(SELECT package, MAX(date) AS maxdate
									FROM packages_history
									GROUP BY package
								) p2 
							ON p.package = p2.package AND p.date = p2.maxdate
						) laststatus
					ON laststatus.package = p.package
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
	
	/* ---------------------------------------------------------------------------------------
		Retourne un tableau contenant les informations d'un package
	---------------------------------------------------------------------------------------- */		
	function get_package_detail($mydb,$packid){
		
		$query = '
		SELECT 
			p.name,p.upload_date,p.autorun,p.package,p.created,p.project,p.server,p.user,p.comment,
			u.username
			from packages p LEFT JOIN users u on u.id = p.uploaded_by
			WHERE p.package="'.$packid.'"';
	
		  try {  
			$stmt = $mydb->prepare($query); 
            $result = $stmt->execute(); 
	
        } 
        catch(PDOException $ex){ die("Failed to run query: " . $ex->getMessage()); } 
		return $stmt->fetch();
	}
	
	/* ---------------------------------------------------------------------------------------
		Retourne un tableau contenant l'ensemble des états d'un package
	---------------------------------------------------------------------------------------- */		
	function get_package_history($mydb,$packid){
		
		$query = '
		SELECT 
			state, substate, comment,date,id
		FROM packages_history
		WHERE package = "' . $packid . '" ORDER BY date DESC
		';			
		  try {  
			$stmt = $mydb->prepare($query); 
            $result = $stmt->execute(); 
	
        } 
        catch(PDOException $ex){ die("Failed to run query: " . $ex->getMessage()); } 
		return $stmt->fetchAll();
	}
	/* ---------------------------------------------------------------------------------------
		Retourne un tableau contenant l'ensemble des fichiers de logs pour un $pack_hist_id donné
	---------------------------------------------------------------------------------------- */	
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
	
	
	/* ---------------------------------------------------------------------------------------
		Retourne l'ensemble des informations relatives à un fichier de log stocké en base (y compris son contenu)
		Si l'utilisateur n'est pas admin on s'assurera que le fichier est bien associé à un package que l'utilisateur a chargé
	---------------------------------------------------------------------------------------- */	
	
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
						ON p.package = h.package
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
		return $stmt->fetch();
	}
	
	/* ---------------------------------------------------------------------------------------
		Vérifie que l'utilisateur en cours est bien authorisé à visualiser le pacjage specifié en paramètre 
		Retourne TRUE si le package existe et qu'il a été chargé par l'utilisateur en cours
		Retourne FALSE sinon
	---------------------------------------------------------------------------------------- */	
	function isUserAllowedToViewPackage($mydb,$pack){

		$query = 'SELECT uploaded_by FROM packages WHERE package="'.$pack.'"';
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
	/* ---------------------------------------------------------------------------------------
		Retourne un texte d'erreur en fonction d'un code d'erreur passé en paramètre
		Sert pour l'affichage d'un bandeau d'erreur sur la page d'acceuil suite à une redirection
	---------------------------------------------------------------------------------------- */	
	function displayErrorMessage($errCode){
		switch ($errCode){
			case 1 : $msg = "Vous devez être connecté en tant qu'administrateur pour accéder à cette partie de l'application.";break;
			case 2 : $msg = "Il manque un paramètre dans l'URL ... ";break;
			case 3 : $msg = "Pour changer un mot de passe il faut être connecté et seul les administrateurs peuvent modifier les mots de passe des autres utilisateurs.";break;
			case 4 : $msg = "Vous devez être connecté pour accéder à cette partie de l'application.";break;
			case 5 : $msg = "Vous avez tenté de télécharger un fichier qui n'existe pas ou sur lequel vous n\'avez pas de droits.";break;
			case 6 : $msg = "Problème d'authentification.<BR>Merci de vérifier votre nom d'utilisateur et mot de passe.";break;
			case 7 : $msg = "Vous avez tenté d'éditer les informations d'un autre utiliseateur alors que vous n'êtes pas administrateur!";break;
			case 8 : $msg = "Vous avez tenté d'obtenir des informations sur un package que vous n'avez pas chargé alors que vous n'êtes pas administrateur!";break;
			case 9 : $msg = "Ce nom d'utilisateur n'existe pas.";break;
			
		
			default : 	$msg = "";break;
		 }			
		return $msg;
	}
	
	/* ---------------------------------------------------------------------------------------
		Retourne TRUE si un package est déjà présent en base avec de nom, FALSE sinon
	---------------------------------------------------------------------------------------- */	
	function isNewPackage($mydb,$package){
		$query ='SELECT COUNT(package) as nb FROM packages WHERE package="'.$package.'"';
		 try {  
			$stmt = $mydb->prepare($query); 
            $result = $stmt->execute(); 
			$res = $stmt->fetch();
			} 
        catch(PDOException $ex){ die("Failed to run query: " . $ex->getMessage()); } 
		if ($res['nb']==0){
			return true;
		}
		else{
			return false;	
		}
	}
	
	/* ---------------------------------------------------------------------------------------
		Retourne un message d'information en fonction d'un code d'information passé en paramètre
		Sert pour l'affichage d'un bandeau d'information sur la page d'acceuil suite à une redirection
	---------------------------------------------------------------------------------------- */	
	function displayInfoMessage($errCode){
		switch ($errCode){
			case 1 : $msg = 'Bonjour <strong>'.$_SESSION['user']['username'].'</strong>.<BR>Vous êtes connecté en tant qu\'administrateur...';break;
			case 2 : $msg = 'Bonjour <strong>'.$_SESSION['user']['username'].'</strong>.<BR>Vous êtes connecté en tant qu\'utilisateur...';break;
			case 3 : $msg = 'Vous avez été correctement deconnecté';break;
			default : 	$msg = "";break;
		 }			
		return $msg;
	}
	
	/* ---------------------------------------------------------------------------------------
		Sert à convertir une taiile  en octet en taille en o / Ko ou Mo
	---------------------------------------------------------------------------------------- */	
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
	/* ---------------------------------------------------------------------------------------
		Fonction pour écrire dans un fichier de log 
		Crée un fichier par jour 
		Pour chaque ligne on ajoute des informations (IP et date)
	---------------------------------------------------------------------------------------- */	
	function writeLog($msg){
		
		$log  = "User: ".$_SERVER['REMOTE_ADDR'].' - '.date("F j, Y, g:i a"). '------------------------- : '.$msg . PHP_EOL;
		
		file_put_contents(LOGFILE . date("Ymj") . '.log', $log, FILE_APPEND);
	}
	
?>