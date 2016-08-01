<?php
require_once('UploadHandler.php');
require_once ('./../../../config.php');

/*
 *  custom class for uploading files, simply extends the UploadHander class
 */
$options = array(
    'delete_type' => 'POST',
    'db_host' => HOST,
    'db_user' => USERNAME,
    'db_pass' => PASSWORD,
    'db_name' => DBNAME,
    'db_table' => DB_PACKAGETABLE,
	'db_history' => DB_PACKAGEHISTORYTABLE
);

/*class CustomStdClass extends stdClass { 
 public $uploaded_by ;
}*/

class CustomUploadHandler extends UploadHandler {
    
	protected function initialize() {
		
		try { 
			$options = array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'); 
			$connString = 'mysql:host=' . $this->options['db_host'] . ';dbname='.$this->options['db_name'] . ';charset=utf8';
			$this->db = new PDO($connString, $this->options['db_user'], $this->options['db_pass'], $options); 
			$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
			$this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
		}
	 
		catch(PDOException $ex){ 
			die("Failed to connect to the database: " . $ex->getMessage());
		}
	 

		parent::initialize();
	
    }

    protected function handle_form_data($file, $index) {
		$file->uploaded_by = @$_REQUEST['uploaded_by'][$index];
		
    }
	
    protected function handle_file_upload($uploaded_file, $name, $size, $type, $error, $index = null, $content_range = null) {
        
		//$file = new CustomStdClass();
		$file = new \stdClass();
		
		$file->name = $name;
		$file->size = $this->fix_integer_overflow((int)$size);
		$file->type = $type;
		
		if ($this->validate($uploaded_file, $file, $error, $index)) {
						
			$this->handle_form_data($file, $index);
			$upload_dir = $this->get_upload_path();
			
			if (!is_dir($upload_dir)) {
				mkdir($upload_dir, $this->options['mkdir_mode'], true);
			}
			
			$file_path = $this->get_upload_path($file->name);
			$append_file = $content_range && is_file($file_path) && $file->size > $this->get_file_size($file_path);
			
			if ($uploaded_file && is_uploaded_file($uploaded_file)) {
				// multipart/formdata uploads (POST method uploads)
				if ($append_file) {
					file_put_contents(
						$file_path,
					   // fopen($uploaded_file, 'r'),
						FILE_APPEND
					);
				} 
				else 
				{
					if (!move_uploaded_file($uploaded_file, $file_path)){
						// Si l'utilisateur apache n'a pas les droit ou FS plein sur le répertoire de dépôt
						$file->error = $this->get_error_message('movetorepo');
					}
					
				}
			}	
			else {
				// Non-multipart uploads (PUT method support)
				file_put_contents(
					$file_path,
					fopen('php://input', 'r'),
					$append_file ? FILE_APPEND : 0
				);
			}
		
			// EXTRACT ZIP INFO
			if (!extension_loaded('zip')) 
			{ 
				$file->error="L'extension zip pour php n'est pas installée!" ;
				unlink ($file_path);
			}
			else{

				$zip = new ZipArchive;		
				$res = $zip->open($file_path);
				if ($res === TRUE) 
				{
					/*if (!$zip->setPassword ( ZIP_PASSWORD )){
						$file->error="Le mot de passe pour ouvrir l'archive n'est pas valide!" ;
						unlink ($file_path);
					}*/

						$string = $zip->getArchiveComment();
						$arr = explode(ZIPCOMMENT_SEPARATOR,$string);

						$result = array();
						foreach($arr as $value)
						{
							$val = explode(ZIPCOMMENT_SEPARATOR_PARAMVAL,$value);
							$result[trim($val[0])] = trim($val[1]);
						}
									
						$package="";
						$autorun="";
						$created="";
						$project="";
						$server="";
						$user="";
						$comment="";
						
						if (isset($result['Package'])){
							$package=$result['Package'];
						}
						if (isset($result['AutoRun'])){
							$autorun=$result['AutoRun'];
						}
						if (isset($result['Comment'])){
							$comment=$result['Comment'];
						}	
						if (isset($result['Created'])){
							$created=$result['Created'];
							try{
								$creationDate = new DateTime($created);
							}
							catch (Exception $e){
								$creationDate=null;
							}
						}
						if (isset($result['Project'])){
							$project=$result['Project'];
						}
						if (isset($result['Server'])){
							$server=$result['Server'];
						}
						if (isset($result['User'])){
							$user=$result['User'];
						}
						/* Vérifier les champs obligatoires :
							PACKAGE qui doit être présent et unique
						*/
						
						if ($package==""){
							$file->error="Impossible d'extraire l'ID du package depuis l'archive zip";
							unlink($file_path);
						}
						else if (!isNewPackage($this->db,$package)){
							$file->error='Impossible d\'importer plusieurs fois le même package ('.$package .')';
							unlink($file_path);
						}
						$zip->close();
					}
				//} 
				else 
				{
					$err = getZipStatus($res);
					$file->error = 'Probleme avec le ZIP : ' .$err;
					unlink($file_path);
				}
			}
			/*
			$file_size = $this->get_file_size($file_path, $append_file);
			if ($file_size === $file->size) {
			/* No need to set URL 
				$file->url = $this->get_download_url($file->name);
			*/
			/* No need to handle image files 
				if ($this->is_valid_image_file($file_path)) {
					$this->handle_image_file($file_path, $file);
				}
			
			} 
			/*else {
				$file->size = $file_size;
				if (!$content_range && $this->options['discard_aborted_uploads']) {
					unlink($file_path);
					$file->error = $this->get_error_message('abort');
				}
			}
			
			$file->size = $file_size;	*/
			
			$this->set_additional_file_properties($file);
		}		
		
		/*
		*	Now Log to the DB 
		*/

		if (empty($file->error)) {
			 try { 
				$this->db->beginTransaction();
				$sql = 'INSERT INTO `'.$this->options['db_table']
                .'` (`name`, `size`, `uploaded_by`,`upload_date`,`type`,`package`,`autorun`,`created`,`project`,`server`,`user`,`comment`)'
				.' VALUES (:name, :size, :uploaded_by, NOW(), :type, :package, :autorun, :created, :project, :server, :user, :comment )';
				$query = $this->db->prepare($sql);
				$query->bindParam(':name', $file->name, PDO::PARAM_STR);
				$query->bindParam(':size', $file->size, PDO::PARAM_INT);
				$query->bindParam(':uploaded_by', $file->uploaded_by, PDO::PARAM_INT);
				$query->bindParam(':type', $file->type, PDO::PARAM_STR);
				$query->bindParam(':package', $package, PDO::PARAM_STR);
				$query->bindParam(':autorun', $autorun, PDO::PARAM_STR);
				if ($creationDate==null){
					$query->bindValue(':created', $creationDate, PDO::PARAM_INT);	
				}
				else{
					$query->bindValue(':created', date_format($creationDate,'Y-m-d H:i:s'), PDO::PARAM_STR);
				}
				$query->bindParam(':project', $project, PDO::PARAM_STR);
				$query->bindParam(':server', $server, PDO::PARAM_STR);
				$query->bindParam(':user', $user, PDO::PARAM_STR);
				$query->bindParam(':comment', $comment, PDO::PARAM_STR);
				$query->execute();
				$package_id =  $this->db->lastInsertId();
				if ($package_id == 0){
					$this->db->rollback();
				}
				else{
					$file->info = MSG_UPLOAD_OK;
					//error_log ("Just inserted a pck : " .$sql . " with id = " . $package_id);
				
					$sql = 'INSERT INTO `'.$this->options['db_history']
					.'` (`package_id`, `state`, `substate`,`comment`,`date`)'
					.' VALUES (:package_id, "UPLOAD", "OK", "'. MSG_UPLOAD_OK .'", NOW())';
					$query = $this->db->prepare($sql);
					$query->bindParam(':package_id', $package_id,PDO::PARAM_INT);
					$query->execute();
					$this->db->commit();
				}
			 }
			catch(PDOException $ex){ 
				$file->error = $ex->getMessage() . " \n SQL QUERY : " . $sql ;
				//die("Failed to run query: " . $ex->getMessage()); 
			} 
        
		}
		return $file;
	}

  protected function set_additional_file_properties($file) {
        parent::set_additional_file_properties($file);
        /*if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $sql = 'SELECT `id`, `type`, `title`, `description` FROM `'
                .$this->options['db_table'].'` WHERE `name`=?';
            $query = $this->db->prepare($sql);
            $query->bind_param('s', $file->name);
            $query->execute();
            $query->bind_result(
                $id,
                $type,
                $title,
                $description
            );
            while ($query->fetch()) {
                $file->id = $id;
                $file->type = $type;
                $file->title = $title;
                $file->description = $description;
            }
		*/
		if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            
			try{
				$sql = 'SELECT `id`, `uploaded_by` FROM `'
					.$this->options['db_table'].'` WHERE `name`=:name';
				$query = $this->db->prepare($sql);
				$query->bindParam(':name', $file->name,PDO::PARAM_STR);
				$query->execute();

				while ($row = $query->fetch()) {
					$file->id = $row['id'];
					$file->uploaded_by = $row['uploaded_by'];
				}
			}
			catch(PDOException $ex){ 
				$file->error = "Problème avec la base de données: " . $ex->getMessage();
				//die("Failed to connect to the database: " . $ex->getMessage());
			}
        }
		
    }
 public function delete($print_response = true) {
		$response = parent::delete(false);
        foreach ($response as $name => $deleted) {
            if ($deleted) {
				/*try { 
					$sql = 'DELETE FROM `'.$this->options['db_table'].'` WHERE `name`=:name';
					$query = $this->db->prepare($sql);
					$query->bindParam(':name', $name, PDO::PARAM_STR);
					$query->execute();	
				}
				catch(PDOException $ex){ 
					die("Failed to run query: " . $ex->getMessage()); 
				}
				*/
			} 
			else{
			//	error_log('Here we need to force a response genre - FILE TREATED')	;
				$response[$name] = true;
			}

        } 
      //  error_log("end extended delete funtion");
	//	error_log('my response is ...' . print_r($response,true));
		return $this->generate_response($response, $print_response);
  }


	protected function get_file_object_fromDB($file_name) {
        error_log('--- get_file_object FROM DB('. $file_name. ')---');
		
		
		//if ($this->is_valid_file_object($file_name)) {
		//	error_log('This file is valid');
            $file = new \stdClass();
			
			try { 
				$sql = 'SELECT `name`,`size`, `id`,`url` FROM `'.$this->options['db_table'] . '` WHERE `name` = :name';
				$query = $this->db->prepare($sql);
				$query->bindParam(':name', $file_name, PDO::PARAM_STR);
				$query->execute();
			//	error_log ("Just inserted a pck : " .$sql);
				$row = $query->fetch();
				$file->name = $row ['name'];
				$file->size = $row ['size'];
				$file->url = $row['url'];
				$this->set_additional_file_properties($file);
				return $file;
				
			 }
			catch(PDOException $ex){ 
				die("Failed to run query: " . $ex->getMessage()); 
			} 
        return null;
    }
	// FETCH FILE LIST FROM DATABASE INSTEAD OF SCANDIR
	protected function get_file_objects($iteration_method = 'get_file_object_fromDB') {
        // DO NOTHING
		return array();
		// DO NOTHING
		
		// SCAN  $upload_dir on the web server (server/php/files)
		/*
		error_log("-- get file objects (extended class)--");
		
		try{
			$sql = 'SELECT `name` FROM `'.$this->options['db_table']
                .'` WHERE `status`= "UPLOADED"';
            $query = $this->db->prepare($sql);
            $query->execute();
			error_log ('Any packages found to display from the DB? : ' . $query->rowCount());
		}
		catch(PDOException $ex){ 
			 error_log ('Failed to connect to the database:');
			die("Failed to connect to the database: " . $ex->getMessage());
		}
       /*
	    return array_values(array_filter(array_map(
            array($this, $iteration_method),
            scandir($upload_dir)
        )));
	   
	   
	   $stack = array();
		while ($row = $query->fetch()) {
			error_log('adding '. $row['name']);
			array_push($stack, $row['name']) ;
		}
    
		return array_values(array_filter(array_map(
			array($this, $iteration_method),
			$stack
		)));
		*/
    }

  public function get($print_response = true) {
        if ($print_response && $this->get_query_param('download')) {
            return $this->download();
        }
       // error_log("-- Extended  GET ---");
		$file_name = $this->get_file_name_param();
	//	error_log("filename (from this->get_file_name_param) : " . $file_name);
        if ($file_name) {
            $response = array(
                $this->get_singular_param_name() => $this->get_file_object($file_name)
            );
        } else {
			//error_log("NO we don't have a file_name");
            /*$response = array(
                $this->options['param_name'] => $this->get_file_objects()
            );*/
			
			/* GET FILE OBJECTS FROM DB */ 
			$response = array(
                $this->options['param_name'] => $this->get_file_objects()
            );
        }
		//error_log("Will genereate a response based on this" . print_r($response,true));
        //error_log("-- End UploadHandler GET (next step is generate response) ---");
		return $this->generate_response($response, $print_response);
    }

public function post($print_response = true) {
        error_log('--- Entering function POST from extended');
		if ($this->get_query_param('_method') === 'DELETE') {
            return $this->delete($print_response);
        }
        $upload = $this->get_upload_data($this->options['param_name']);
        // Parse the Content-Disposition header, if available:
        $content_disposition_header = $this->get_server_var('HTTP_CONTENT_DISPOSITION');
        $file_name = $content_disposition_header ?
            rawurldecode(preg_replace(
                '/(^[^"]+")|("$)/',
                '',
                $content_disposition_header
            )) : null;
        // Parse the Content-Range header, which has the following form:
        // Content-Range: bytes 0-524287/2000000
        $content_range_header = $this->get_server_var('HTTP_CONTENT_RANGE');
        $content_range = $content_range_header ?
            preg_split('/[^0-9]+/', $content_range_header) : null;
        $size =  $content_range ? $content_range[3] : null;
       // error_log('Creating the $files array');
		$files = array();
        if ($upload) {
           // error_log('$upload is TRUE');
			//error_log('$upload content is ...' . print_r($upload,true));
			if (is_array($upload['tmp_name'])) {
                // param_name is an array identifier like "files[]",
                // $upload is a multi-dimensional array:
                foreach ($upload['tmp_name'] as $index => $value) {
					$files[] = $this->handle_file_upload(
                        $upload['tmp_name'][$index],
                        $file_name ? $file_name : $upload['name'][$index],
                        $size ? $size : $upload['size'][$index],
                        $upload['type'][$index],
                        $upload['error'][$index],
                        $index,
                        $content_range
                    );
                }
            } else {
				
                // param_name is a single object identifier like "file",
                // $upload is a one-dimensional array:
                //error_log('upload is NOT an array');
				$files[] = $this->handle_file_upload(
                    isset($upload['tmp_name']) ? $upload['tmp_name'] : null,
                    $file_name ? $file_name : (isset($upload['name']) ?
                            $upload['name'] : null),
                    $size ? $size : (isset($upload['size']) ?
                            $upload['size'] : $this->get_server_var('CONTENT_LENGTH')),
                    isset($upload['type']) ?
                            $upload['type'] : $this->get_server_var('CONTENT_TYPE'),
                    isset($upload['error']) ? $upload['error'] : null,
                    null,
                    $content_range
                );
            }
        }
        //error_log('$this->options[param_name] : ' . $this->options['param_name']);
		$response = array($this->options['param_name'] => $files);
		//error_log('Before generate response from POST, $files is ... '. print_r($files,true));
		//error_log("Before generate response from POST, the response is ... ". print_r($response,true));
		
        return $this->generate_response($response, $print_response);
    }

	//Retourne un message d'erreur en fonction d'un code d'erreur de la clasee php ZIP
	
}




// options to pass to the upload handler object
/*$options = [
   // ANY OPTIONS, SUCH AS FILE UPLOAD LOCATION
   'upload_url' => 'ftp://localhost/files/',
];*/

// finally, instantiate the new class   
//error_log("Error message\n", 3, "/var/log/apache2/php.log");
//$upload_handler = new CustomUploadHandler($options);

$upload_handler = new CustomUploadHandler($options);
//$upload_handler = new CustomUploadHandler();

function getZipStatus($zipErrorCode){
		switch ($zipErrorCode){
			case ZipArchive::ER_OK : 					$err = 'Pas d\'erreur';										break;
			case ZipArchive::ER_MULTIDISK : 			$err = 'Les archives zip Multi-Disk ne sont pas supportées';break;
			case ZipArchive::ER_RENAME : 				$err = 'Le renommage du fichier temporaire a échoué';		break;
			case ZipArchive::ER_CLOSE :         		$err = 'Problème à la fermeture de l\'archive zip';			break;
			case ZipArchive::ER_SEEK :           		$err = 'Problème avec la fonction SEEK';					break;
			case ZipArchive::ER_READ :          		$err = 'Problème de lecture';								break;
			case ZipArchive::ER_WRITE :         		$err = 'Problème d\'écriture';								break;
			case ZipArchive::ER_CRC : 					$err = 'Problème de CRC';									break;
			case ZipArchive::ER_ZIPCLOSED :				$err = 'L\'archive zip a été fermée';						break;
			case ZipArchive::ER_NOENT :    				$err = 'Fichier zip inexistant';							break;
			case ZipArchive::ER_EXISTS : 				$err = 'Le fichier zip existe déjà';						break;
			case ZipArchive::ER_OPEN : 					$err = 'Impossible d\'ouvrir le fichier zip';				break;
			case ZipArchive::ER_TMPOPEN :     			$err = "Echec lors de la création du fichier temporaire";	break;
			case ZipArchive::ER_ZLIB :   				$err = 'Erreur zlib';										break;
			case ZipArchive::ER_MEMORY :  				$err = 'Erreur d\'allocation mémoire';						break;
			case ZipArchive::ER_CHANGED : 				$err = "L'entrée a été modifiée";							break;
			case ZipArchive::ER_COMPNOTSUPP :  			$err = "La méthode de compression n'est pas supportée";		break;
			case ZipArchive::ER_EOF :   				$err = 'Fin de fichier prématurée (EOF)';					break;
			case ZipArchive::ER_INVAL :       			$err = 'Argument invalide';									break;
			case ZipArchive::ER_NOZIP : 				$err = "Ce fichier n'est pas une archive zip valide";		break;
			case ZipArchive::ER_INTERNAL : 				$err = 'Erreur interne';									break;
			case ZipArchive::ER_INCONS :        		$err = "L'archive zip est inconsistante";					break;
			case ZipArchive::ER_REMOVE : 				$err = "Impossible de supprimer le fichier";				break;
			case ZipArchive::ER_DELETED :  				$err = "L'entrée a été supprimée";							break;
			default : $err="";
		}
		return $err;
	}

	
?>