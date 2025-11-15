<?php
include_once '../includes/db_connect.php';
include_once '../includes/functions.php';

session_start();

if (!isset($_SESSION['user_id']) || !check_user_role($mysqli, $_SESSION['user_id'], 'admin')) {
    header('Location: ../index.php');
    exit();
}

$user_id_to_delete = $_GET['id'] ?? null;
if ($user_id_to_delete) {
    // Prevent admin from deleting themselves
    if ($user_id_to_delete == $_SESSION['user_id']) {
        $_SESSION['error_message'] = "You cannot delete your own account.";
    } else {
        // Check for related records in sales_invoices
        $stmt = $mysqli->prepare("SELECT COUNT(*) FROM sales_invoices WHERE user_id = ?");
        $stmt->bind_param("i", $user_id_to_delete);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        if ($count > 0) {
            $_SESSION['error_message'] = "Cannot delete user: They are linked to existing sales invoices.";
        } else {
            $delete_stmt = $mysqli->prepare("DELETE FROM users WHERE id = ?");
            $delete_stmt->bind_param("i", $user_id_to_delete);
            if ($delete_stmt->execute()) {
                $_SESSION['success_message'] = "User deleted successfully.";
            } else {
                $_SESSION['error_message'] = "Error deleting user.";
            }
            $delete_stmt->close();
        }
    }
}

header('Location: manage_users.php');
exit();
