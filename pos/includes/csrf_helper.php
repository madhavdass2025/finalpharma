<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Generates and stores a CSRF token in the session.
 * @return string The generated token.
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validates a submitted CSRF token against the one in the session.
 * Exits with an error if the token is invalid.
 * @param string $token The token from the form submission.
 */
function validate_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        // In a real app, you might log this attempt or show a generic error page.
        die('CSRF token validation failed.');
    }
    // Once used, the token should be regenerated to prevent reuse
    unset($_SESSION['csrf_token']);
}

/**
 * Generates a hidden input field with the CSRF token.
 */
function csrf_input() {
    $token = generate_csrf_token();
    echo '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
 * Checks if the request is a POST request and validates the CSRF token if so.
 */
function check_csrf() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token'])) {
            die('CSRF token is missing from the form submission.');
        }
        validate_csrf_token($_POST['csrf_token']);
    }
}
?>