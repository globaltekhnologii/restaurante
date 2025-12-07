<?php
require_once 'config.php';

// Cerrar sesión
session_destroy();
header('Location: login.php');
exit();
?>
