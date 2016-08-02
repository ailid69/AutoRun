<?php

/*-------------------------------------------------------------------------------------------------	
	Affiche une barre de navigation - Ce fichier doit être inclu dans chacune des pages de l'application
	La liste des actions disponibles est dynamique selon si : 
	L'utilisateur est connecté ou pas
	L'utilisatuer est connecté en tant qu'administrateur ou simple utilisateur
-------------------------------------------------------------------------------------------------*/

    require_once 'config.php';
	
	/*print_r($_SESSION);
	echo '<BR>$_POST : ';
	print_r($_POST);
		echo '<BR>$_FILES : ';
	print_r($_FILES);
	echo '<BR>$_SERVER : ';
	print_r($_SERVER);
	*/
	//phpinfo ();

?>
<style type="text/css">
  </style>
<nav class="navbar navbar-inverse navbar-fixed-top ">

  <div class="container-fluid">
 
    <div class="navbar-header">
	<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false">      
	  <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
      <a class="navbar-brand">AutoRun - Une application pour automatiser le passage de script</a>
    </div>
    
	<div class="collapse navbar-collapse navbar-fixed-top" id="bs-example-navbar-collapse-1">

      <ul class="nav navbar-nav navbar-right">
        <?php
		
		/*-------------------------------------------------------------------------------------------------
			Si l'utilisateur est connecté et qu'il est administateur
			On affiche une liste déroulante contenant l'ensemble des actions réservées aux administrateurs 
		-------------------------------------------------------------------------------------------------*/
		
		 if(isset($_SESSION['user']['isadmin']) && ($_SESSION['user']['isadmin']))
		 {
		echo '		 
		<li class="dropdown">
          <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Fonctions Admin<span class="caret"></span></a>
          <ul class="dropdown-menu">
			<li><a href="/users.php">Gestion des utilisateurs</a></li>
            <li><a href="/adduser.php">Ajout d\'un utilisateur</a></li>
            <li role="separator" class="divider"></li>
			<li><a target="_blank" href="/docs/Autorun.pdf">Schéma cycle de vie</a></li>

          </ul>
        </li>
		';
		}
		
		/*-------------------------------------------------------------------------------------------------
			Si l'utilisateur est connecté (qu'il soit administrateur ou simple utilisateur)
			On affiche une liste déroulante contenant l'ensemble des actions réservées aux utilisateurs 
		-------------------------------------------------------------------------------------------------*/
			
		if( isset($_SESSION['user']))
		 {
		echo '
		<li class="dropdown">
          <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Fonctions utilisateur<span class="caret"></span></a>
          <ul class="dropdown-menu">
            <li><a href="/packages.php">Gestion des packages</a></li>
			<li><a href="/fileupload/fileupload.php">Chargement de packages</a></li>
          </ul>

        </li>
		
		<li class="dropdown">
			<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Bonjour <STRONG>' . $_SESSION['user']['username'] .'</STRONG><span class="caret"></span></a>
			<ul class="dropdown-menu">
				<li><a href="/edituser.php?id='.$_SESSION['user']['id'].'">Editer mes informations</a></li>	
				<li><a href="/changepwd.php?id='.$_SESSION['user']['id'].'">Changer de mot de passe</a></li>	
				<li role="separator" class="divider"></li>
				<li><a href="/logout.php">Déconnexion</a></li>
			</ul>
		</li>
		';
		 }
		 
		/*-------------------------------------------------------------------------------------------------
			Si l'utilisateur n'est pas connecté
			On affiche une liste déroulante contenant le formulaire de connection 
		-------------------------------------------------------------------------------------------------*/
		
		if(empty($_SESSION['user'])) 
		{
		echo '
		<li class="dropdown">
			<a class="dropdown-toggle" href="#" data-toggle="dropdown">Connexion<strong class="caret"></strong></a>
			<div class="dropdown-menu" style="padding: 15px; padding-bottom: 0px;">
                <div class="panel panel-default">
					<div class="panel-body">
						<form action="/login.php" method="post"> 
							Utilisateur:<br /> 
							<input type="text" name="username" /> 
							<br /><br /> 
							Mot de passe:<br /> 
							<input type="password" name="password" value="" /> 
							<br /><br /> 
							<input type="submit" class="btn btn-info" value="Login" /> 
						</form> 
					</div>
				</div>
            </div>          
		</li>  
		';
		}	
		?>
	</ul>

    </div><!-- /.navbar-collapse -->
  </div><!-- /.container-fluid -->
</nav>