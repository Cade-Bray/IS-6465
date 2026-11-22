<?php
require_once __DIR__ . '/auth.php';
startSecureSession();
$cssVersion = file_exists(__DIR__ . '/../css/styles.css') ? filemtime(__DIR__ . '/../css/styles.css') : time();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/styles.css?v=<?= $cssVersion ?>">
  <title>Peer Tutoring Hub</title>
  <style>
    html {
      height: 100%;
    }
    body {
      min-height: 100%;
      min-height: 100vh;
      min-height: 100dvh;
      display: flex !important;
      flex-direction: column !important;
    }
    main {
      flex: 1 0 auto !important;
    }
    footer {
      margin-top: auto !important;
    }
    h1 {
      background-color: transparent !important;
      padding: 0 !important;
      color: inherit !important;
    }
    .card-header h1,
    .card-title,
    .card h1 {
      background-color: transparent !important;
      padding: 0 !important;
      color: inherit !important;
    }
    .card form,
    form.stacked-form {
      display: block !important;
      max-width: 100% !important;
      margin: 0 !important;
      padding: 0 !important;
      background-color: transparent !important;
      box-shadow: none !important;
      border-radius: 0 !important;
    }
  </style>
</head>
<body class="d-flex flex-column min-vh-100">
