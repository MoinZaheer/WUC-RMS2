<?php
require_once __DIR__ . '/check_auth.php';

$role = $_SESSION['role'];
$pageTitle = $pageTitle ?? ucfirst(str_replace('_', ' ', $_GET['page'] ?? 'Dashboard'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= $pageTitle ?> - WUC RMS</title>
  <link rel="stylesheet" href="/style.css">
</head>
<body>
<div class="layout">

  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="logo">
      <img src="/images/Logo.jpg" alt="WUC Logo" />
    </div>
    <?php require_once __DIR__ . '/nav.php'; ?>
  </aside>

  <!-- Topbar + Content -->
  <div class="main-wrapper">
    <header class="topbar">
      <div class="topbar-left">
        <h1><?= $pageTitle ?></h1>
      </div>
      <div class="user-info">
        <span>Welcome, <?= ucfirst($role) ?></span>
        <a href="/index.php?page=logout">Logout</a>
      </div>
    </header>

    <main class="page-content">
      <?php require $contentPath; ?>
    </main>
  </div>

</div>

<!-- for nav submenus -->
<script>
  window.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll(".sidebar li button").forEach(btn => {
      btn.addEventListener("click", () => {
        btn.parentElement.classList.toggle("active");
      });
    });
  });
</script>

</body>
</html>
