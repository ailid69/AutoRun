<?php 
    require_once 'config.php'; 
    unset($_SESSION['user']);
    header("Location: index.php?info=3"); 
    die("Redirecting to: index.php");
?>