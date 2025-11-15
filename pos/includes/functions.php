<?php
// General security and helper functions

// CSRF Token Functions
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// User Role Check Function
function check_user_role($conn, $user_id, $required_role) {
    $stmt = $conn->prepare("SELECT r.role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row['role_name'] === $required_role;
    }
    return false;
}

// Dashboard Data Fetching Functions
function get_total_sales_for_date($conn, $date) {
    $stmt = $conn->prepare("SELECT SUM(net_amount) as total FROM sales_invoices WHERE invoice_date = ? AND status = 'completed'");
    $stmt->bind_param("s", $date);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc()['total'] ?? 0;
}

function get_collections_by_payment_method($conn, $date, $user_id = null) {
    $query = "SELECT payment_method, SUM(amount_paid) as total
              FROM payments p JOIN sales_invoices si ON p.invoice_id = si.id
              WHERE si.invoice_date = ?";
    $params = ["s", $date];
    if ($user_id) {
        $query .= " AND si.user_id = ?";
        $params[0] .= "i";
        $params[] = $user_id;
    }
    $query .= " GROUP BY payment_method";

    $stmt = $conn->prepare($query);
    $stmt->bind_param(...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $collections = [];
    while ($row = $result->fetch_assoc()) {
        $collections[$row['payment_method']] = $row['total'];
    }
    return $collections;
}

function get_sales_trend($conn, $period = 'monthly') {
    if ($period == 'monthly') {
        $query = "SELECT DATE_FORMAT(invoice_date, '%Y-%m') as month, SUM(net_amount) as total
                  FROM sales_invoices WHERE status = 'completed' GROUP BY month ORDER BY month DESC LIMIT 12";
    } else { // weekly
        $query = "SELECT DATE_FORMAT(invoice_date, '%Y-%u') as week, SUM(net_amount) as total
                  FROM sales_invoices WHERE status = 'completed' GROUP BY week ORDER BY week DESC LIMIT 12";
    }
    $result = $conn->query($query);
    $trend = [];
    while($row = $result->fetch_assoc()){
        $trend[$row[array_keys($row)[0]]] = $row['total'];
    }
    return array_reverse($trend, true);
}


function get_low_stock_items($conn, $product_id = null) {
    $query = "SELECT p.product_name, SUM(sb.current_qty) as current_qty
              FROM products p
              JOIN stock_batches sb ON p.id = sb.product_id
              GROUP BY p.id
              HAVING current_qty < p.reorder_level";
    if($product_id){
         $query .= " AND p.id = " . (int)$product_id;
    }
    return $conn->query($query)->fetch_all(MYSQLI_ASSOC);
}


function get_expiring_soon_items($conn, $days_threshold, $product_id = null) {
    $query = "SELECT p.product_name, sb.batch_number, sb.expiry_date
              FROM stock_batches sb
              JOIN products p ON sb.product_id = p.id
              WHERE sb.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)";
     if($product_id){
         $query .= " AND p.id = " . (int)$product_id;
    }
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $days_threshold);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function get_today_sales_for_user($conn, $user_id){
    $stmt = $conn->prepare("SELECT invoice_number, net_amount FROM sales_invoices WHERE user_id = ? AND invoice_date = CURDATE()");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function get_favorite_products($conn){
    return $conn->query("SELECT id, product_name FROM products WHERE is_favorite = 1 LIMIT 20")->fetch_all(MYSQLI_ASSOC);
}
?>
