<?php require_once __DIR__ . "/../config/db.php"; ?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Futsal Bajjar - Admin</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    /* Minimal Admin Header */
    .admin-header {
      background: #111827;
      color: white;
      padding: 15px 25px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .admin-header .logo a {
      color: white;
      text-decoration: none;
      font-size: 20px;
      font-weight: 600;
    }

    .admin-header .logo i {
      color: #22c55e;
      margin-right: 8px;
    }

    .admin-header .welcome {
      color: #e5e7eb;
      font-size: 15px;
    }

    .admin-header .welcome i {
      margin-right: 6px;
      color: #22c55e;
    }

    main {
      padding: 20px;
    }

    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background: #f9fafb;
    }
  </style>
</head>
<body>
<header class="admin-header">
  <div class="logo">
    <a href="index.php.php">
      <i class="fas fa-futbol"></i> FutsalBajjar
    </a>
  </div>

  <div class="welcome">
    <i class="fas fa-user"></i> Welcome, Admin
  </div>
</header>

<main>
  <!-- Your admin content will go here -->
</main>