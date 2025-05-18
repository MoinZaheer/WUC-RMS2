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
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- sselect2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>





</head>
<body>
<div class="layout">

  <!-- sidebar -->
  <aside class="sidebar">
    <div class="logo">
      <img src="/images/Logo.jpg" alt="WUC Logo" />
    </div>
    <?php require_once __DIR__ . '/nav.php'; ?>
  </aside>

  <!-- topbar + Content -->
  <div class="main-wrapper">
    <header class="topbar">
      <div class="topbar-left">
        <h1><?= $pageTitle ?></h1>
      </div>
      <div class="user-info">
        <span>Welcome, <?= ucfirst($role) ?></span>
        <a href="/index.php">Logout</a>
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


<!-- for the select2 dropdowns -->
<script>
  document.addEventListener('DOMContentLoaded', function () {
    $('.select2').select2();
  });

  $(document).ready(function () {
  $('.select2').select2({
    width: 'resolve' // auto-fit width
  });
});

// Focus the search input when dropdown opens
$(document).on('select2:open', () => {
  document.querySelector('.select2-container--open .select2-search__field')?.focus();
});


</script>

</body>
</html>
