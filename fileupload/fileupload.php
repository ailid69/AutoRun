<?php

/*-------------------------------------------------------------------------------------------------
	Page pour le chargement de nouveaux packages
-------------------------------------------------------------------------------------------------*/		

    require_once './../config.php';
	
	/*-------------------------------------------------------------------------------------------------
		Redirige vers la page d'accueil si l'utilisateur n'est pas connecté 
	-------------------------------------------------------------------------------------------------*/		
    if(empty($_SESSION['user'])) 
    {
        header("Location: /index.php?msg=4");
        die("Redirecting to index.php"); 
    }
	else 
?>
<!DOCTYPE HTML>

<html lang="en">
<head>
<!-- Force latest IE rendering engine or ChromeFrame if installed -->
<!--[if IE]><meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"><![endif]-->
<meta charset="utf-8">
<title>AutoRun - Chargement de packages</title>

<!-- Bootstrap styles -->
<link rel="stylesheet" href="/css/bootstrap.min.css" media="screen">
<link rel="stylesheet" href="/css/jquery.fileupload.css">
<style type="text/css">
        .hero-unit { background-color: #fff; }
        .center { display: block; margin: 0 auto; }
		 body { padding-top: 70px; }		
    </style>
</head>

<body>
<?php include('./../myNavBar.php'); ?>

<div class="container hero-unit">
	<div class="panel panel-default">
		<div class="panel-heading">
			<p><h2>Chargement de packages</h2></p>
		</div>
		<div class="panel-body">
	
			<form id="fileupload" action="/php/fileupload/server/php/index.php" method="POST" enctype="multipart/form-data">
			<!-- Redirect browsers with JavaScript disabled to the origin page -->
			<noscript><input type="hidden" name="redirect" value="./fileupload.php"></noscript>
			<!-- The fileupload-buttonbar contains buttons to add/delete files and start/cancel the upload -->
			<div class="row fileupload-buttonbar">
				<div class="col-lg-7">
					<!-- The fileinput-button span is used to style the file input field as button -->
					<span class="btn btn-success fileinput-button">
						<i class="glyphicon glyphicon-plus"></i>
						<span>Ajouter des fichiers ...</span>
							<input type="file" name="files[]" multiple>
					</span>
					<button type="submit" class="btn btn-primary start">
						<i class="glyphicon glyphicon-upload"></i>
						<span>Démarrer</span>
					</button>
					<button type="reset" class="btn btn-warning cancel">
						<i class="glyphicon glyphicon-ban-circle"></i>
						<span>Annuler</span>
					</button>
					
<!--  Suppresion de l'option de suppression des package déjà chargés              
			   <button type="button" class="btn btn-danger delete">
                    <i class="glyphicon glyphicon-trash"></i>
                    <span>Supprimer</span>
                </button>
                <input type="checkbox" class="toggle">
-->
             
					<span class="fileupload-process"></span>
				</div>
            <!-- The global progress state -->
            <div class="col-lg-5 fileupload-progress fade">
                <!-- The global progress bar -->
                <div class="progress progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100">
                    <div class="progress-bar progress-bar-success" style="width:0%;"></div>
                </div>
                <!-- The extended global progress state -->
                <div class="progress-extended">&nbsp;</div>
            </div>
        
        <!-- Table listant l'ensemble des chargements -->
        <table role="presentation" class="table table-striped"><tbody class="files"></tbody></table>
    </form>
	</div>
</div>
	
<!-- 
Template pouyr l'affichage des fichiers disponibles en chargement 
-->
<script id="template-upload" type="text/x-tmpl">
$('#fileupload').fileupload({
    url: 'server/php/'
}).on('fileuploadsubmit', function (e, data) {
    data.formData = data.context.find(':input').serializeArray();
});

{% for (var loopvar=0, file; file=o.files[loopvar]; loopvar++) { %}
	
	<tr class="template-upload fade">
        <td>
            <p class="name">{%=file.name%}</p>
			<input type="hidden" name="uploaded_by[]" value="<?php echo $_SESSION['user']['id'];?>">
            <strong class="error text-danger"></strong>
        </td>
        <td>
            <p class="size">En cours de traitement...</p>
            <div class="progress progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><div class="progress-bar progress-bar-success" style="width:0%;"></div></div>
        </td>
		
        <td>
            {% if (!loopvar && !o.options.autoUpload) { %}
                <button class="btn btn-primary start" disabled>
                    <i class="glyphicon glyphicon-upload"></i>
                    <span>Démarrer</span>
                </button>
            {% } %}
            {% if (!loopvar) { %}
                <button class="btn btn-warning cancel">
                    <i class="glyphicon glyphicon-ban-circle"></i>
                    <span>Annuler</span>
                </button>
            {% } %}
        </td>
    </tr>
{% } %}
</script>

<!--  
Template pour l'affichage des fichiers chargées
-->
<script id="template-download" type="text/x-tmpl">
{% for (var i=0, file; file=o.files[i]; i++) { %}
    <tr class="template-download fade">
        <td>
            <p class="name">
                    <span>Nom de fichier : {%=file.name%}</span><br>
					<span>Package : {%=file.package%}</span><br>
					<span>Serveur : {%=file.server%}</span><br>
					<span>Utilisateur : {%=file.user%}</span>
            </p>
            {% if (file.error) { %}
                <div><span class="label label-danger">Erreur</span> {%=file.error%}</div>
             {% } else { %}
				<div><span class="label label-success">Info</span> {%=file.info%}</</div>
                {% } %}
        </td>

        <td>
            <span class="size">{%=o.formatFileSize(file.size)%}</span>
        </td>
        <td>
		
            {% if (file.deleteUrl) { %}
			<!--
                <button class="btn btn-danger delete" data-type="{%=file.deleteType%}" data-url="{%=file.deleteUrl%}"{% if (file.deleteWithCredentials) { %} data-xhr-fields='{"withCredentials":true}'{% } %}>
                    <i class="glyphicon glyphicon-trash"></i>
                    <span>Supprimer</span>
                </button>
                <input type="checkbox" name="delete" value="1" class="toggle">
			-->	
            {% } else { %}
                <button class="btn btn-warning cancel">
                    <i class="glyphicon glyphicon-ban-circle"></i>
                    <span>Annuler</span>
                </button>
            {% } %}
        </td>
    </tr>
{% } %}
</script>
<!--script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script-->
 <script src="js/jquery.min.js"></script>
<!-- The jQuery UI widget factory, can be omitted if jQuery UI is already included -->
<script src="js/vendor/jquery.ui.widget.js"></script>
<!-- The Templates plugin is included to render the upload/download listings -->
<!--script src="//blueimp.github.io/JavaScript-Templates/js/tmpl.min.js"></script-->
<script src="js/tmpl.min.js"></script>
<!-- The Load Image plugin is included for the preview images and image resizing functionality -->
<script src="js/load-image.all.min.js"></script>
<!-- The Canvas to Blob plugin is included for image resizing functionality -->
<script src="js/canvas-to-blob.min.js"></script>
<!-- Bootstrap JS is not required, but included for the responsive demo navigation -->
<!--script src="//netdna.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js"></script>
<!-- blueimp Gallery script -->
<!--script src="jquery.blueimp-gallery.min.js"></script>
<!-- The Iframe Transport is required for browsers without support for XHR file uploads -->

 <!--script src="/js/jquery.min.js"></script-->
<script src="/js/bootstrap.min.js"></script>

<script src="js/jquery.iframe-transport.js"></script>
<!-- The basic File Upload plugin -->
<script src="js/jquery.fileupload.js"></script>
<!-- The File Upload processing plugin -->
<script src="js/jquery.fileupload-process.js"></script>
<!-- The File Upload image preview & resize plugin -->
<script src="js/jquery.fileupload-image.js"></script>
<!-- The File Upload audio preview plugin -->
<script src="js/jquery.fileupload-audio.js"></script>
<!-- The File Upload video preview plugin -->
<script src="js/jquery.fileupload-video.js"></script>
<!-- The File Upload validation plugin -->
<script src="js/jquery.fileupload-validate.js"></script>
<!-- The File Upload user interface plugin -->
<script src="js/jquery.fileupload-ui.js"></script>
<!-- The main application script -->
<script src="js/main.js"></script>
<!-- The XDomainRequest Transport is included for cross-domain file deletion for IE 8 and IE 9 -->
<!--[if (gte IE 8)&(lt IE 10)]>
<script src="js/cors/jquery.xdr-transport.js"></script>
<![endif]-->
</body>
</html>
