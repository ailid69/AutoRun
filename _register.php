<?php 
    require_once 'config.php';
    if(!empty($_POST)) 
    { 
        // Ensure that the user fills out fields 
        if(empty($_POST['username'])) 
        { die("Please enter a username."); } 
        if(empty($_POST['password'])) 
        { die("Please enter a password."); } 
       
         
        // Check if the username is already taken
        $query = " 
            SELECT 
                1 
            FROM users 
            WHERE 
                username = :username 
        "; 
        $query_params = array( ':username' => $_POST['username'] ); 
        try { 
            $stmt = $db->prepare($query); 
            $result = $stmt->execute($query_params); 
        } 
        catch(PDOException $ex){ die("Failed to run query: " . $ex->getMessage()); } 
        $row = $stmt->fetch(); 
        if($row){ die("This username is already in use"); } 
       
         
        // Add row to database 
        $query = " 
            INSERT INTO users ( 
                username, 
                password, 
				firstname,
				lastname,
				email,
                salt 
            ) VALUES ( 
                :username, 
                :password, 
				'test',
				'test',
				'david.aili@hp.com',
                :salt
            ) 
        "; 
         
        // Security measures
        $salt = dechex(mt_rand(0, 2147483647)) . dechex(mt_rand(0, 2147483647)); 
        $password = hash('sha256', $_POST['password'] . $salt); 
        for($round = 0; $round < 65536; $round++){ $password = hash('sha256', $password . $salt); } 
        $query_params = array( 
            ':username' => $_POST['username'], 
            ':password' => $password, 
            ':salt' => $salt, 
        ); 
        try {  
            $stmt = $db->prepare($query); 
            $result = $stmt->execute($query_params); 
        } 
        catch(PDOException $ex){ die("Failed to run query: " . $ex->getMessage()); } 
        header("Location: index.php"); 
        die("Redirecting to index.php"); 
    } 
?>
<!-- Author: Michael Milstead / Mode87.com
     for Untame.net
     Bootstrap Tutorial, 2013
-->
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>AutoRun - Ajout d'utilisateurs</title>
    <meta name="description" content="My EDF application for Packages">
    <meta name="author" content="david.aili@hpe.com">

    <script src="/js/jquery.min.js"></script>
    <!--<script src="/assets/bootstrap.min.js"></script>
    <link href="/assets/bootstrap.min.css" rel="stylesheet" media="screen">-->
	<script src="/js/bootstrap.js"></script>
	<link href="/css/bootstrap.css" rel="stylesheet" media="screen">
    <style type="text/css">
        .hero-unit { background-color: #fff; }
        .center { display: block; margin: 0 auto; }
    </style>
</head>

<body>

<div class="navbar navbar-fixed-top navbar-inverse">
  <div class="navbar-inner">
    <div class="container">
      <a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </a>
      <a class="brand">My EDF application for Packages</a>
      <div class="nav-collapse">
        <ul class="nav pull-right">
          <li><a href="index.php">Return Home</a></li>
        </ul>
      </div>
    </div>
  </div>
</div>

<div class="container hero-unit">
    <h1>Register</h1> <br /><br />
    <form action="register.php" method="post"> 
        <label>Username:</label> 
        <input type="text" name="username" value="" /> 
        <label>Password:</label> 
        <input type="password" name="password" value="" /> <br /><br />
       <!--
	   <p style="color:darkred;">* You may enter a false email address if desired. This demo database does not store addresses for purposes outside of this tutorial.</p><br />
       -->
	   <input type="submit" class="btn btn-info" value="Register" /> 
    </form>
</div>

</body>
</html>