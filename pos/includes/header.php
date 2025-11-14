<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Simple check to ensure the user is logged in before loading any module page.
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CPMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        body { display: flex; min-height: 100vh; flex-direction: column; }
        .main-container { display: flex; flex: 1; }
        .sidebar { min-width: 250px; max-width: 250px; background-color: #f8f9fa; }
        .content { flex-grow: 1; padding: 20px; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">Clinic Pharmacy MS</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <span class="navbar-text">Welcome, <?php echo htmlspecialchars($_SESSION["name"]); ?></span>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">Sign Out</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="main-container">