<?php
class Auth {
    /**
     * Checks if a user is logged in and has the required role.
     * If not, it redirects them to the appropriate page.
     *
     * @param array $allowed_roles An array of role_ids that are allowed to access the page.
     */
    public static function check_access($allowed_roles) {
        // Initialize the session if it's not already started
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        // Check if the user is logged in
        if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
            header("location: ../index.php");
            exit;
        }

        // Check if the user's role is in the allowed roles array
        if (!in_array($_SESSION["role_id"], $allowed_roles)) {
            // Redirect to an unauthorized page or the dashboard
            header("location: unauthorized.php");
            exit;
        }
    }
}
?>