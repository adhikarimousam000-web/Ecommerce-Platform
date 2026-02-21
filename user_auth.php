<?php
require_once __DIR__ . "/../config/db.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: login.php");
  exit;
}
?>
