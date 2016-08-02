<?php
	/*-------------------------------------------------------------------------------------------------
		Page pour visualiser le dernier état de chaque package
		La page est disponible pour le role administrateur (voit tous les packages)	
		et pour le role utilisateur (ne voit que les package qu'il a chargé)
	-------------------------------------------------------------------------------------------------*/	
    require_once 'config.php';
	
	/*-------------------------------------------------------------------------------------------------
		Redirige vers la page d'accueil si l'utilisateur n'est pas connecté 
	-------------------------------------------------------------------------------------------------*/		
    if(empty($_SESSION['user']))
    {
        header("Location: index.php?msg=4");
        die("Redirecting to index.php"); 
    }
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>AutoRun - Gestion des packages</title>
    <meta name="description" content="My EDF application for Packages">
    <meta name="author" content="david.aili@hpe.com">

    <link href="/css/bootstrap.min.css" rel="stylesheet" media="screen">
	<link href="/css/dataTables.bootstrap.min.css" rel="stylesheet" media="screen">
	<link href="/css/jquery.dataTables.min.css" rel="stylesheet" media="screen">
	<style type="text/css">
        .hero-unit { background-color: #fff; }
        .center { display: block; margin: 0 auto; }
		 body { padding-top: 70px; }
    </style>
</head>
<body>
<?php include('myNavBar.php'); ?>

<div class="container hero-unit">
	<div class="panel panel-default">

  <!-- Default panel contents -->
  <div class="panel-heading">
   <p><h2>Gestion des Packages</h2></p>
  </div>
  <div class="panel-body">
  
  
<?php 

?>
 
<form role="form" action="packages_history.php" method="get">
<span style="font-size:11px;">
  <table id="packages" class="table" cellspacing="0" width="100%">
	<thead> 
		<tr> 
			<th>Nom du package</th>
			<th>Nom du fichier</th>
			<th>Phase</th>
			<th>Statut</th>
			<th>Date du dernier statut</th>
			<th>Téléchargé par</th> 
			<th>Téléchargé le</th>
			<th>Projet</th>
			<th>Serveur</th>
			<th>Utilisateur</th>
			<th>Historique</th>
		</tr> 
	</thead> 
	<tbody> 
	
	<?php
	   $result = show_last_status_by_package($db,$_SESSION['user']['id'],$_SESSION['user']['isadmin']);
	   
	   foreach ($result as $row){
		   switch ($row['substate']){
				case "OK": $mysublabel = "label label-success";break;
				case "ERROR" : $mysublabel = "label label-danger";break;
				default : 	$mysublabel = "label label-default";break;
		 	}
			 switch ($row['state']){
				case "UPLOAD": $mylabel = "label label-warning";break;
				case "CONTROL" : $mylabel = "label label-primary";break;
				case "HANDLE" : $mylabel = "label label-info";break;
				case "IMPORT" : $mylabel = "label label-default";break;
				case "EXECUTE" : $mylabel = "label label-success";break;
				default : 	$mylabel = "label label-danger";break;
		   }
		echo '
		<tr> <th data-html="true" data-container="body" data-toggle="tooltip2" title="Taille : '.sizetohumanreadable($row['size']) .'<BR>Généré à partir de : '.$row['autorun'].'">' . $row['package'] . ' </th> 
		
			<td>' . $row['name'] .'</td> 
			<td align="center"><span class="label '. $mylabel .'">' . $row['state'] .'</span></td> 
			<td align="center"> <span class="label '. $mysublabel .'" data-toggle="tooltip" title="'. $row['comment'] .'">' . $row['substate'] .'</span></td>
			<td>' . $row['date'] . '</td>
			<td>' . $row['username'] .'</td> 
			<td>' . $row['upload_date'] .'</td> 
			<td>' . $row['project'] .'</td> 
			<td>' . $row['server'] .'</td> 
			<td>' . $row['user'] .'</td>
			<td><button type="submit" class="btn btn-info btn btn-primary btn-xs" name="viewHistory" value="'.$row['package'] .'">Historique</button></a></td>
		</tr>
		';
		}
		
		?>

	</tbody> 
</table>
	</span>
</form> 	

</div>
</div>
</div>

	<script src="/js/jquery.min.js"></script>
	<script src="/js/bootstrap.min.js"></script>
	<script src="/js/dataTables.bootstrap.js"></script>
	<script src="/js/jquery.dataTables.min.js"></script>
	<script type="text/javascript">
		$(document).ready(function(){
			$('[data-toggle="tooltip"]').tooltip(); 
			$('[data-toggle="tooltip2"]').tooltip(); 
		});
</script>
	<script type="text/javascript">
			$(document).ready(function() {
				$('#packages').DataTable( {


	language: {
        processing:     "Traitement en cours...",
        search:         "Rechercher&nbsp;:",
        lengthMenu:    "Afficher _MENU_ &eacute;l&eacute;ments",
        info:           "Affichage de l'&eacute;lement _START_ &agrave; _END_ sur _TOTAL_ &eacute;l&eacute;ments",
        infoEmpty:      "Affichage de l'&eacute;lement 0 &agrave; 0 sur 0 &eacute;l&eacute;ments",
        infoFiltered:   "(filtr&eacute; de _MAX_ &eacute;l&eacute;ments au total)",
        infoPostFix:    "",
        loadingRecords: "Chargement en cours...",
        zeroRecords:    "Aucun &eacute;l&eacute;ment &agrave; afficher",
        emptyTable:     "Aucune donnée disponible dans le tableau",
        paginate: {
            first:      "Premier",
            previous:   "Pr&eacute;c&eacute;dent",
            next:       "Suivant",
            last:       "Dernier"
        },
        aria: {
            sortAscending:  ": activer pour trier la colonne par ordre croissant",
            sortDescending: ": activer pour trier la colonne par ordre décroissant"
        }
    },

     "order": [[ 4, "desc" ]],
	
	"columns": [
    null,
    null,
    null,
    null,
    null,
	null,
    null,
    null,
	null,
    null,
	{ "orderable": false }
  ]
} );

			} )

</script>

</body>
</html>