<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . "/../config/db.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: ../login.php");
  exit;
}

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "ADMIN") {
  echo "Access denied: You are not admin.";
  exit;
}
?>
