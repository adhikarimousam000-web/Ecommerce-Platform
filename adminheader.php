<?php require_once __DIR__ . "/../config/db.php"; ?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Fustal Bajjar</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
      margin: 0;
      background: #fafafa;
    }

    header {
      padding: 12px 24px;
      border-bottom: 1px solid #eaeaea;
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: white;
      position: sticky;
      top: 0;
      z-index: 1000;
      box-shadow: 0 2px 10px rgba(0,0,0,0.02);
    }

    .logo a {
      font-size: 20px;
      font-weight: 600;
      color: #1a1a1a;
      text-decoration: none;
      letter-spacing: -0.5px;
    }

    .logo a:hover {
      color: #000;
    }

    nav {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    nav a {
      margin: 0;
      padding: 8px 16px;
      text-decoration: none;
      color: #4a4a4a;
      font-size: 14px;
      font-weight: 500;
      transition: all 0.2s ease;
      border-radius: 6px;
    }

    nav a:not(.user-dropdown-trigger):hover {
      background: #f5f5f5;
      color: #000;
    }

    /* User Dropdown Styles */
    .user-dropdown {
      position: relative;
      display: inline-block;
    }

    .user-dropdown-trigger {
      display: flex;
      align-items: center;
      gap: 8px;
      background: #1a1a1a;
      color: white !important;
      padding: 8px 20px !important;
      border-radius: 30px;
      font-weight: 500;
      font-size: 14px;
      cursor: pointer;
      border: 1px solid #1a1a1a;
      transition: all 0.2s ease;
    }

    .user-dropdown-trigger:hover {
      background: #333;
      border-color: #333;
    }

    .user-dropdown-trigger i {
      font-size: 12px;
      transition: transform 0.2s ease;
    }

    .user-dropdown:hover .user-dropdown-trigger i {
      transform: rotate(180deg);
    }

    .dropdown-menu {
      position: absolute;
      top: calc(100% + 8px);
      right: 0;
      background: white;
      border: 1px solid #eaeaea;
      border-radius: 12px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
      min-width: 200px;
      opacity: 0;
      visibility: hidden;
      transform: translateY(-10px);
      transition: all 0.2s ease;
      z-index: 1001;
    }

    .user-dropdown:hover .dropdown-menu {
      opacity: 1;
      visibility: visible;
      transform: translateY(0);
    }

    .dropdown-header {
      padding: 16px;
      border-bottom: 1px solid #eaeaea;
    }

    .dropdown-header .user-name {
      font-weight: 600;
      color: #1a1a1a;
      font-size: 14px;
    }

    .dropdown-header .user-email {
      font-size: 12px;
      color: #6c757d;
      margin-top: 4px;
    }

    .dropdown-items {
      padding: 8px;
    }

    .dropdown-item {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px 16px;
      color: #4a4a4a;
      text-decoration: none;
      font-size: 14px;
      border-radius: 8px;
      transition: all 0.2s ease;
    }

    .dropdown-item:hover {
      background: #f5f5f5;
      color: #000;
    }

    .dropdown-item i {
      width: 16px;
      color: #6c757d;
      font-size: 14px;
    }

    .dropdown-item:hover i {
      color: #000;
    }

    .dropdown-divider {
      height: 1px;
      background: #eaeaea;
      margin: 8px 0;
    }

    /* Auth buttons styling */
    .auth-buttons {
      display: flex;
      gap: 8px;
    }

    .auth-buttons a {
      padding: 8px 20px;
      border-radius: 30px;
      font-weight: 500;
    }

    .auth-buttons a:first-child {
      background: transparent;
      color: #1a1a1a;
      border: 1px solid #1a1a1a;
    }

    .auth-buttons a:first-child:hover {
      background: #1a1a1a;
      color: white;
    }

    .auth-buttons a:last-child {
      background: #1a1a1a;
      color: white;
      border: 1px solid #1a1a1a;
    }

    .auth-buttons a:last-child:hover {
      background: #333;
    }

    main {
      padding: 24px;
      max-width: 1200px;
      margin: 0 auto;
    }

    .btn {
      padding: 8px 12px;
      border: none;
      background: #111;
      color: #fff;
      border-radius: 8px;
      cursor: pointer;
    }

    .input {
      padding: 10px;
      width: 100%;
      max-width: 420px;
      border: 1px solid #ccc;
      border-radius: 8px;
    }

    .card {
      border: 1px solid #ddd;
      border-radius: 10px;
      padding: 12px;
      margin: 10px 0;
    }

    /* Responsive */
    @media (max-width: 768px) {
      header {
        padding: 12px 16px;
        flex-wrap: wrap;
      }

      nav {
        flex-wrap: wrap;
        justify-content: flex-end;
      }

      .user-dropdown-trigger span {
        display: none;
      }

      .user-dropdown-trigger {
        padding: 8px 12px !important;
      }

      .dropdown-menu {
        position: fixed;
        top: auto;
        right: 16px;
        width: calc(100% - 32px);
        max-width: 300px;
      }
    }
  </style>
</head>
<body>
<header>
  <div class="logo">
    <a href="index.php"><b>Fustal Bajjar</b></a>
  </div>
  
  <nav>
    <a href="index.php">Home</a>
    <a href="products.php">Products</a>
    <a href="cart.php">Cart</a>

    <?php else: ?>
      <!-- Auth Buttons for non-logged in users -->
      <div class="auth-buttons">
        <a href="login.php">Login</a>
        <a href="register.php">Register</a>
      </div>
    <?php endif; ?>
  </nav>
</header>
<main>
  <!-- Your main content here -->
</main>