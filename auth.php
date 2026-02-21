<?php
require_once __DIR__ . "/../config/db.php";

function requireLogin() {
  if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
  }
}

function isAdmin() {
  return isset($_SESSION['role']) && $_SESSION['role'] === "ADMIN";
}
?>
