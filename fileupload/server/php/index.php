<?php

/* -----------------------------------------------------------
	Appellé à chaque action de la page fileupload
	Renvoit une réponse sous la forme d'un fichier json 

	Exemple : {"files":[{"name":"13ValidARCHIVE1.zip","size":952,"type":"application\/x-zip-compressed","uploaded_by":"15","info":"Le package est bien t\u00e9l\u00e9charg\u00e9, en attente de contr\u00f4le","package":"ABCDEFGjk","server":"PCYYY589","user":"oracle"}]}

	Si file->info existe on affichera un label Success avec le contenu de file->info ainsi que des inforamtions relatives au package chargé
	So file -> error existe on afficher un label Error avec le contenu de file->error
----------------------------------------------------------- */

require_once ('./../../../config.php');


/* -----------------------------------------------------------
	Initialisation des options du gestionnaire de téléchargement
----------------------------------------------------------- */
$options = array(
    'delete_type' => 'POST',
    'db_host' => HOST,
    'db_user' => USERNAME,
    'db_pass' => PASSWORD,
    'db_name' => DBNAME,
    'db_table' => DB_PACKAGETABLE,
	'db_history' => DB_PACKAGEHISTORYTABLE
); 
 
class UploadHandler
{
    protected $options;

    protected $error_messages = array(
        1 => 'Le fichier téléchargé excède la directive upload_max_filesize dans php.ini',
        2 => 'Le fichier téléchargé excède la directive MAX_FILE_SIZE spécifié dans le formulaire HTML',
        3 => 'Le fichier téléchargé n\'a été que partiellement téléchargé',
        4 => 'Aucun fichier téléchargé',
        6 => 'Il manque un répertoire temporaire',
        7 => 'Echec lors de l\'écriture du fichier sur le disque',
        8 => 'Une extension PHP a stoppé le téléchargement du fichier',
        'post_max_size' => 'Le fichier téléchargé excède la directive post_max_size directive dans php.ini',
        'max_file_size' => 'Le fichier est trop volumineux',
        'min_file_size' => 'Le fichier est trop petit',
        'accept_file_types' => 'Ce type de fichier n\'est pas authorisé',
        'max_number_of_files' => 'Le nombre maximum de fichier a été atteint',
        'max_width' => 'Image exceeds maximum width',
        'min_width' => 'Image requires a minimum width',
        'max_height' => 'Image exceeds maximum height',
        'min_height' => 'Image requires a minimum height',
        'abort' => 'Le téléchargement du fichier a été annulé',
        'image_resize' => 'Failed to resize image',
		'name_used' => 'Ce fichier a déjà été téléchargé',
		'movetorepo' => 'Ce package n\'a pas pu être copié sur le répertoire de dépôt'
    );

    public function __construct($options = null, $initialize = true, $error_messages = null) {
		
		$this->response = array();
        $this->options = array(
            'script_url' => $this->get_full_url().'/'.$this->basename($this->get_server_var('SCRIPT_NAME')),
			'upload_dir' => UPLOAD_DIR,
			'upload_url' => UPLOAD_DIR,
            'input_stream' => 'php://input',
            'user_dirs' => false,
            'mkdir_mode' => 0755,
            'param_name' => 'files',
            'delete_type' => 'DELETE',
            'access_control_allow_origin' => '*',
            'access_control_allow_credentials' => false,
            'access_control_allow_methods' => array(
                'OPTIONS',
                'HEAD',
                'GET',
                'POST',
                'PUT',
                'PATCH',
                'DELETE'
            ),
            'access_control_allow_headers' => array(
                'Content-Type',
                'Content-Range',
                'Content-Disposition'
            ),
            'redirect_allow_target' => '/^'.preg_quote(
              parse_url($this->get_server_var('HTTP_REFERER'), PHP_URL_SCHEME)
                .'://'
                .parse_url($this->get_server_var('HTTP_REFERER'), PHP_URL_HOST)
                .'/', // Trailing slash to not match subdomains by mistake
              '/' // preg_quote delimiter param
            ).'/',
            // Enable to provide file downloads via GET requests to the PHP script:
            //     1. Set to 1 to download files via readfile method through PHP
            //     2. Set to 2 to send a X-Sendfile header for lighttpd/Apache
            //     3. Set to 3 to send a X-Accel-Redirect header for nginx
            // If set to 2 or 3, adjust the upload_url option to the base path of
            // the redirect parameter, e.g. '/files/'.
            'download_via_php' => false,
            // Read files in chunks to avoid memory limits when download_via_php
            // is enabled, set to 0 to disable chunked reading of files:
            'readfile_chunk_size' => 10 * 1024 * 1024, // 10 MiB
            // Defines which files can be displayed inline when downloaded:
            'inline_file_types' => '/\.(gif|jpe?g|png)$/i',
            // Defines which files (based on their names) are accepted for upload:
            
			//'accept_file_types' => '/.+$/i',
			'accept_file_types' => ACCEPT_FILE_TYPES,
			
            // The php.ini settings upload_max_filesize and post_max_size
            // take precedence over the following max_file_size setting:
            'max_file_size' => null,
            'min_file_size' => 1,
            // The maximum number of files for the upload directory:
            'max_number_of_files' => null,
            // Defines which files are handled as image files:
            'image_file_types' => '/\.(gif|jpe?g|png)$/i',
            // Use exif_imagetype on all files to correct file extensions:
            'correct_image_extensions' => false,
            // Image resolution restrictions:
            'max_width' => null,
            'max_height' => null,
            'min_width' => 1,
            'min_height' => 1,
            // Set the following option to false to enable resumable uploads:
            'discard_aborted_uploads' => false,
            // Set to 0 to use the GD library to scale and orient images,
            // set to 1 to use imagick (if installed, falls back to GD),
            // set to 2 to use the ImageMagick convert binary directly:
            'image_library' => 1,
            // Uncomment the following to define an array of resource limits
            // for imagick:
            /*
            'imagick_resource_limits' => array(
                imagick::RESOURCETYPE_MAP => 32,
                imagick::RESOURCETYPE_MEMORY => 32
            ),
            */
            // Command or path for to the ImageMagick convert binary:
            'convert_bin' => 'convert',
            // Uncomment the following to add parameters in front of each
            // ImageMagick convert call (the limit constraints seem only
            // to have an effect if put in front):
            /*
            'convert_params' => '-limit memory 32MiB -limit map 32MiB',
            */
            // Command or path for to the ImageMagick identify binary:
            'identify_bin' => 'identify',
            'image_versions' => array(
                // The empty image version key defines options for the original image:
                '' => array(
                    // Automatically rotate images based on EXIF meta data:
                    'auto_orient' => true
                ),
                // Uncomment the following to create medium sized images:
                /*
                'medium' => array(
                    'max_width' => 800,
                    'max_height' => 600
                ),
                */
                'thumbnail' => array(
                    // Uncomment the following to use a defined directory for the thumbnails
                    // instead of a subdirectory based on the version identifier.
                    // Make sure that this directory doesn't allow execution of files if you
                    // don't pose any restrictions on the type of uploaded files, e.g. by
                    // copying the .htaccess file from the files directory for Apache:
                    //'upload_dir' => dirname($this->get_server_var('SCRIPT_FILENAME')).'/thumb/',
                    //'upload_url' => $this->get_full_url().'/thumb/',
                    // Uncomment the following to force the max
                    // dimensions and e.g. create square thumbnails:
                    //'crop' => true,
                    'max_width' => 80,
                    'max_height' => 80
                )
            ),
            'print_response' => true
        );
        if ($options) {
            $this->options = $options + $this->options;
        }
        if ($error_messages) {
            $this->error_messages = $error_messages + $this->error_messages;
        }
        if ($initialize) {
            $this->initialize();
        }
    }

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
		
        switch ($this->get_server_var('REQUEST_METHOD')) {
            case 'OPTIONS':
            case 'HEAD':
                $this->head();
                break;
            case 'GET':
                $this->get($this->options['print_response']);
                break;
            case 'PATCH':
            case 'PUT':
            case 'POST':
                $this->post($this->options['print_response']);
                break;
            case 'DELETE':
                $this->delete($this->options['print_response']);
                break;
            default:
                $this->header('HTTP/1.1 405 Method Not Allowed');
        }
    }

    protected function get_full_url() {
        $https = !empty($_SERVER['HTTPS']) && strcasecmp($_SERVER['HTTPS'], 'on') === 0 ||
            !empty($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
                strcasecmp($_SERVER['HTTP_X_FORWARDED_PROTO'], 'https') === 0;
        return
            ($https ? 'https://' : 'http://').
            (!empty($_SERVER['REMOTE_USER']) ? $_SERVER['REMOTE_USER'].'@' : '').
            (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : ($_SERVER['SERVER_NAME'].
            ($https && $_SERVER['SERVER_PORT'] === 443 ||
            $_SERVER['SERVER_PORT'] === 80 ? '' : ':'.$_SERVER['SERVER_PORT']))).
            substr($_SERVER['SCRIPT_NAME'],0, strrpos($_SERVER['SCRIPT_NAME'], '/'));
    }

    protected function get_user_id() {
        @session_start();
        return session_id();
    }

    protected function get_user_path() {
        if ($this->options['user_dirs']) {
            return $this->get_user_id().'/';
        }
        return '';
    }

    protected function get_upload_path($file_name = null, $version = null) {
        $file_name = $file_name ? $file_name : '';
        if (empty($version)) {
            $version_path = '';
        } else {
            $version_dir = @$this->options['image_versions'][$version]['upload_dir'];
            if ($version_dir) {
                return $version_dir.$this->get_user_path().$file_name;
            }
            $version_path = $version.'/';
        }
		
        return $this->options['upload_dir'].$this->get_user_path()
            .$version_path.$file_name;
    }

    protected function get_query_separator($url) {
        return strpos($url, '?') === false ? '?' : '&';
    }

    protected function get_download_url($file_name, $version = null, $direct = false) {
        if (!$direct && $this->options['download_via_php']) {
            $url = $this->options['script_url']
                .$this->get_query_separator($this->options['script_url'])
                .$this->get_singular_param_name()
                .'='.rawurlencode($file_name);
            if ($version) {
                $url .= '&version='.rawurlencode($version);
            }
            return $url.'&download=1';
        }
        if (empty($version)) {
            $version_path = '';
        } else {
            $version_url = @$this->options['image_versions'][$version]['upload_url'];
            if ($version_url) {
                return $version_url.$this->get_user_path().rawurlencode($file_name);
            }
            $version_path = rawurlencode($version).'/';
        }
        return $this->options['upload_url'].$this->get_user_path()
            .$version_path.rawurlencode($file_name);
    }

    protected function set_additional_file_properties($file) {
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

    // Fix for overflowing signed 32 bit integers,
    // works for sizes up to 2^32-1 bytes (4 GiB - 1):
    protected function fix_integer_overflow($size) {
        if ($size < 0) {
            $size += 2.0 * (PHP_INT_MAX + 1);
        }
        return $size;
    }

    protected function get_file_size($file_path, $clear_stat_cache = false) {
        if ($clear_stat_cache) {
            if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
                clearstatcache(true, $file_path);
            } else {
                clearstatcache();
            }
        }
        return $this->fix_integer_overflow(filesize($file_path));
    }

    protected function is_valid_file_object($file_name) {
        $file_path = $this->get_upload_path($file_name);
        if (is_file($file_path) && $file_name[0] !== '.') {
            return true;
        }
        return false;
    }

    protected function get_file_object($file_name) {

		if ($this->is_valid_file_object($file_name)) {
            $file = new \stdClass();
            $file->name = $file_name;
            $file->size = $this->get_file_size(
                $this->get_upload_path($file_name)
            );
            $file->url = $this->get_download_url($file->name);
            foreach ($this->options['image_versions'] as $version => $options) {
                if (!empty($version)) {
                    if (is_file($this->get_upload_path($file_name, $version))) {
                        $file->{$version.'Url'} = $this->get_download_url(
                            $file->name,
                            $version
                        );
                    }
                }
            }
            $this->set_additional_file_properties($file);
            return $file;
        }
        return null;
    }

    protected function get_file_objects($iteration_method = 'get_file_object') {
		/* -------------------------------------------------------------------------
		Permet de lister les fichiers visibles lors de la première connexion à la page de chargement de fichiers
		Dans notre cas, on en fait rien
		Si l'on souhaite afficher la liste des fichiers basées sur le contenu de la base de données ou la liste des fichiers disponibles dans le répertoire de dépôt, 
		c'est ici qu'il ajouter les modifications
		-------------------------------------------------------------------------*/
		
		return array();

    }

    protected function count_file_objects() {
        return count($this->get_file_objects('is_valid_file_object'));
    }

    protected function get_error_message($error) {
        return isset($this->error_messages[$error]) ?
            $this->error_messages[$error] : $error;
    }

    public function get_config_bytes($val) {
        $val = trim($val);
        $last = strtolower($val[strlen($val)-1]);
        $val = (int)$val;
        switch ($last) {
            case 'g':
                $val *= 1024;
            case 'm':
                $val *= 1024;
            case 'k':
                $val *= 1024;
        }
        return $this->fix_integer_overflow($val);
    }

    protected function validate($uploaded_file, $file, $error, $index) {
		if ($error) {
            $file->error = $this->get_error_message($error);
            return false;
        }
        		
		// Pour vérifier que le nom de fichier 
		$sql = 'SELECT `name` FROM `'.$this->options['db_table'].'` WHERE `name`=:name';
		$query = $this->db->prepare($sql);
		$query->bindParam(':name', $file->name,PDO::PARAM_STR);
		$query->execute();
		
		if ($query->rowCount()!=0){
			$file->error = $this->get_error_message('name_used');
			return false;
		}
		
		$content_length = $this->fix_integer_overflow(
            (int)$this->get_server_var('CONTENT_LENGTH')
        );
        $post_max_size = $this->get_config_bytes(ini_get('post_max_size'));
        if ($post_max_size && ($content_length > $post_max_size)) {
            $file->error = $this->get_error_message('post_max_size');
            return false;
        }
        if (!preg_match($this->options['accept_file_types'], $file->name)) {
            $file->error = $this->get_error_message('accept_file_types');
            return false;
        }
        if ($uploaded_file && is_uploaded_file($uploaded_file)) {
            $file_size = $this->get_file_size($uploaded_file);
        } else {
            $file_size = $content_length;
        }
        if ($this->options['max_file_size'] && (
                $file_size > $this->options['max_file_size'] ||
                $file->size > $this->options['max_file_size'])
            ) {
            $file->error = $this->get_error_message('max_file_size');
            return false;
        }
        if ($this->options['min_file_size'] &&
            $file_size < $this->options['min_file_size']) {
            $file->error = $this->get_error_message('min_file_size');
            return false;
        }
        if (is_int($this->options['max_number_of_files']) &&
                ($this->count_file_objects() >= $this->options['max_number_of_files']) &&
                // Ignore additional chunks of existing files:
                !is_file($this->get_upload_path($file->name))) {
            $file->error = $this->get_error_message('max_number_of_files');
            return false;
        }

        return true;
    }

    protected function upcount_name_callback($matches) {
        $index = isset($matches[1]) ? ((int)$matches[1]) + 1 : 1;
        $ext = isset($matches[2]) ? $matches[2] : '';
        return ' ('.$index.')'.$ext;
    }

    protected function upcount_name($name) {
        return preg_replace_callback(
            '/(?:(?: \(([\d]+)\))?(\.[^.]+))?$/',
            array($this, 'upcount_name_callback'),
            $name,
            1
        );
    }

    protected function get_unique_filename($file_path, $name, $size, $type, $error,
            $index, $content_range) {
        while(is_dir($this->get_upload_path($name))) {
            $name = $this->upcount_name($name);
        }
        // Keep an existing filename if this is part of a chunked upload:
        $uploaded_bytes = $this->fix_integer_overflow((int)$content_range[1]);
        while (is_file($this->get_upload_path($name))) {
            if ($uploaded_bytes === $this->get_file_size(
                    $this->get_upload_path($name))) {
                break;
            }
            $name = $this->upcount_name($name);
        }
        return $name;
    }

    protected function fix_file_extension($file_path, $name, $size, $type, $error,
            $index, $content_range) {
        // Add missing file extension for known image types:
        if (strpos($name, '.') === false &&
                preg_match('/^image\/(gif|jpe?g|png)/', $type, $matches)) {
            $name .= '.'.$matches[1];
        }
        if ($this->options['correct_image_extensions'] &&
                function_exists('exif_imagetype')) {
            switch (@exif_imagetype($file_path)){
                case IMAGETYPE_JPEG:
                    $extensions = array('jpg', 'jpeg');
                    break;
                case IMAGETYPE_PNG:
                    $extensions = array('png');
                    break;
                case IMAGETYPE_GIF:
                    $extensions = array('gif');
                    break;
            }
            // Adjust incorrect image file extensions:
            if (!empty($extensions)) {
                $parts = explode('.', $name);
                $extIndex = count($parts) - 1;
                $ext = strtolower(@$parts[$extIndex]);
                if (!in_array($ext, $extensions)) {
                    $parts[$extIndex] = $extensions[0];
                    $name = implode('.', $parts);
                }
            }
        }
        return $name;
    }

    protected function trim_file_name($file_path, $name, $size, $type, $error,
            $index, $content_range) {
        // Remove path information and dots around the filename, to prevent uploading
        // into different directories or replacing hidden system files.
        // Also remove control characters and spaces (\x00..\x20) around the filename:
        $name = trim($this->basename(stripslashes($name)), ".\x00..\x20");
        // Use a timestamp for empty filenames:
        if (!$name) {
            $name = str_replace('.', '-', microtime(true));
        }
        return $name;
    }

    protected function get_file_name($file_path, $name, $size, $type, $error,
            $index, $content_range) {
        $name = $this->trim_file_name($file_path, $name, $size, $type, $error,
            $index, $content_range);
        return $this->get_unique_filename(
            $file_path,
            $this->fix_file_extension($file_path, $name, $size, $type, $error,
                $index, $content_range),
            $size,
            $type,
            $error,
            $index,
            $content_range
        );
    }

    protected function handle_file_upload($uploaded_file, $name, $size, $type, $error, $index = null, $content_range = null) {
            
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

				else 
				{
					$err = getZipStatus($res);
					$file->error = 'Probleme avec le ZIP : ' .$err;
					unlink($file_path);
				}
			}
			
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
				
					$sql = 'INSERT INTO `'.$this->options['db_history']
					.'` (`package_id`, `state`, `substate`,`comment`,`date`)'
					.' VALUES (:package_id, "UPLOAD", "OK", "'. MSG_UPLOAD_OK .'", NOW())';
					$query = $this->db->prepare($sql);
					$query->bindParam(':package_id', $package_id,PDO::PARAM_INT);
					$query->execute();
					$this->db->commit();
					
					$file->info = MSG_UPLOAD_OK;
					$file->package = $package;
					$file->server = $server;
					$file->user = $user;
					//error_log ("Just inserted a pck : " .$sql . " with id = " . $package_id);

				}
			 }
			catch(PDOException $ex){ 
				$file->error = $ex->getMessage() . " \n SQL QUERY : " . $sql ;
				//die("Failed to run query: " . $ex->getMessage()); 
			} 
        
		}
		return $file;
    }

    protected function readfile($file_path) {
        $file_size = $this->get_file_size($file_path);
        $chunk_size = $this->options['readfile_chunk_size'];
        if ($chunk_size && $file_size > $chunk_size) {
            $handle = fopen($file_path, 'rb');
            while (!feof($handle)) {
                echo fread($handle, $chunk_size);
                @ob_flush();
                @flush();
            }
            fclose($handle);
            return $file_size;
        }
        return readfile($file_path);
    }

    protected function body($str) {
        echo $str;
    }

    protected function header($str) {
        header($str);
    }

    protected function get_upload_data($id) {
        return @$_FILES[$id];
    }

    protected function get_post_param($id) {
        return @$_POST[$id];
    }

    protected function get_query_param($id) {
        return @$_GET[$id];
    }

    protected function get_server_var($id) {
        return @$_SERVER[$id];
    }

    protected function handle_form_data($file, $index) {
        // Handle form data, e.g. $_POST['description'][$index]
		$file->uploaded_by = @$_REQUEST['uploaded_by'][$index];
		
    }

    protected function get_version_param() {
        return $this->basename(stripslashes($this->get_query_param('version')));
    }

    protected function get_singular_param_name() {
        return substr($this->options['param_name'], 0, -1);
    }

    protected function get_file_name_param() {
        $name = $this->get_singular_param_name();
        return $this->basename(stripslashes($this->get_query_param($name)));
    }

    protected function get_file_names_params() {
        $params = $this->get_query_param($this->options['param_name']);
        if (!$params) {
            return null;
        }
        foreach ($params as $key => $value) {
            $params[$key] = $this->basename(stripslashes($value));
        }
        return $params;
    }

    protected function get_file_type($file_path) {
        switch (strtolower(pathinfo($file_path, PATHINFO_EXTENSION))) {
            case 'jpeg':
            case 'jpg':
                return 'image/jpeg';
            case 'png':
                return 'image/png';
            case 'gif':
                return 'image/gif';
            default:
                return '';
        }
    }

    protected function download() {
        switch ($this->options['download_via_php']) {
            case 1:
                $redirect_header = null;
                break;
            case 2:
                $redirect_header = 'X-Sendfile';
                break;
            case 3:
                $redirect_header = 'X-Accel-Redirect';
                break;
            default:
                return $this->header('HTTP/1.1 403 Forbidden');
        }
        $file_name = $this->get_file_name_param();
        if (!$this->is_valid_file_object($file_name)) {
            return $this->header('HTTP/1.1 404 Not Found');
        }
        if ($redirect_header) {
            return $this->header(
                $redirect_header.': '.$this->get_download_url(
                    $file_name,
                    $this->get_version_param(),
                    true
                )
            );
        }
        $file_path = $this->get_upload_path($file_name, $this->get_version_param());
        // Prevent browsers from MIME-sniffing the content-type:
        $this->header('X-Content-Type-Options: nosniff');
        if (!preg_match($this->options['inline_file_types'], $file_name)) {
            $this->header('Content-Type: application/octet-stream');
            $this->header('Content-Disposition: attachment; filename="'.$file_name.'"');
        } else {
            $this->header('Content-Type: '.$this->get_file_type($file_path));
            $this->header('Content-Disposition: inline; filename="'.$file_name.'"');
        }
        $this->header('Content-Length: '.$this->get_file_size($file_path));
        $this->header('Last-Modified: '.gmdate('D, d M Y H:i:s T', filemtime($file_path)));
        $this->readfile($file_path);
    }

    protected function send_content_type_header() {
        $this->header('Vary: Accept');
        if (strpos($this->get_server_var('HTTP_ACCEPT'), 'application/json') !== false) {
            $this->header('Content-type: application/json');
        } else {
            $this->header('Content-type: text/plain');
        }
    }

    protected function send_access_control_headers() {
        $this->header('Access-Control-Allow-Origin: '.$this->options['access_control_allow_origin']);
        $this->header('Access-Control-Allow-Credentials: '
            .($this->options['access_control_allow_credentials'] ? 'true' : 'false'));
        $this->header('Access-Control-Allow-Methods: '
            .implode(', ', $this->options['access_control_allow_methods']));
        $this->header('Access-Control-Allow-Headers: '
            .implode(', ', $this->options['access_control_allow_headers']));
    }

    public function generate_response($content, $print_response = true) {
		$this->response = $content;
        if ($print_response) {
            $json = json_encode($content);
            $redirect = stripslashes($this->get_post_param('redirect'));
            if ($redirect && preg_match($this->options['redirect_allow_target'], $redirect)) {
                $this->header('Location: '.sprintf($redirect, rawurlencode($json)));
                return;
            }
            $this->head();
            if ($this->get_server_var('HTTP_CONTENT_RANGE')) {
                $files = isset($content[$this->options['param_name']]) ?
                    $content[$this->options['param_name']] : null;
                if ($files && is_array($files) && is_object($files[0]) && $files[0]->size) {
                    $this->header('Range: 0-'.(
                        $this->fix_integer_overflow((int)$files[0]->size) - 1
                    ));
                }
            }
            $this->body($json);
        }
        return $content;
    }

    public function get_response () {
        return $this->response;
    }

    public function head() {
        $this->header('Pragma: no-cache');
        $this->header('Cache-Control: no-store, no-cache, must-revalidate');
        $this->header('Content-Disposition: inline; filename="files.json"');
        // Prevent Internet Explorer from MIME-sniffing the content-type:
        $this->header('X-Content-Type-Options: nosniff');
        if ($this->options['access_control_allow_origin']) {
            $this->send_access_control_headers();
        }
        $this->send_content_type_header();
    }

    public function get($print_response = true) {
        if ($print_response && $this->get_query_param('download')) {
            return $this->download();
        }
		$file_name = $this->get_file_name_param();

        if ($file_name) {
            $response = array(
                $this->get_singular_param_name() => $this->get_file_object($file_name)
            );
        } else {
			$response = array(
                $this->options['param_name'] => $this->get_file_objects()
            );
        }
	
		return $this->generate_response($response, $print_response);
    }

    public function post($print_response = true) {
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

		$files = array();
        if ($upload) {

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
            
        }
		$response = array($this->options['param_name'] => $files);
        return $this->generate_response($response, $print_response);
    }

    public function delete($print_response = true) {
		$file_names = $this->get_file_names_params();
        if (empty($file_names)) {
            $file_names = array($this->get_file_name_param());
        }
        $response = array();
        foreach ($file_names as $file_name) {
            $file_path = $this->get_upload_path($file_name);
            $success = is_file($file_path) && $file_name[0] !== '.' && unlink($file_path);
			
            if ($success) {
                foreach ($this->options['image_versions'] as $version => $options) {
                    if (!empty($version)) {
                        $file = $this->get_upload_path($file_name, $version);
                        if (is_file($file)) {
                            unlink($file);

                        }
                    }
                }
            }
            $response[$file_name] = $success;
			
        }
        return $this->generate_response($response, $print_response);
    }

    protected function basename($filepath, $suffix = null) {
        $splited = preg_split('/\//', rtrim ($filepath, '/ '));
        return substr(basename('X'.$splited[count($splited)-1], $suffix), 1);
    }
}

$upload_handler = new UploadHandler($options);

//Retourne un message d'erreur en fonction d'un code d'erreur de la clasee php ZIP
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
