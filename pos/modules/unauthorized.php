<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Access Denied - CPMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font: 14px sans-serif; text-align: center; padding: 50px; }
        .wrapper { max-width: 600px; margin: 0 auto; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="alert alert-danger">
            <h4 class="alert-heading">Access Denied!</h4>
            <p>You do not have permission to view this page. Your user role does not grant you access to this resource.</p>
            <hr>
            <p class="mb-0">
                Please contact the system administrator if you believe this is an error.
                <a href="dashboard.php">Return to Dashboard</a>
            </p>
        </div>
    </div>
</body>
</html>