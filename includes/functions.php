<?php
require_once 'database.php';

// Admin authentication functions
function isAdminLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function adminLogin($username, $password) {
    global $db;
    
    $stmt = $db->prepare("SELECT id, username, password FROM admin_users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            return true;
        }
    }
    return false;
}

function adminLogout() {
    unset($_SESSION['admin_logged_in']);
    unset($_SESSION['admin_id']);
    unset($_SESSION['admin_username']);
    session_destroy();
}

// Product functions
function getAllProducts() {
    global $db;
    $sql = "SELECT p.*, c.name as category_name FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            ORDER BY p.name";
    return $db->query($sql);
}

function getProductsByCategory($category_id) {
    global $db;
    $stmt = $db->prepare("SELECT p.*, c.name as category_name FROM products p 
                         LEFT JOIN categories c ON p.category_id = c.id 
                         WHERE p.category_id = ? ORDER BY p.name");
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    return $stmt->get_result();
}

function getProductById($id) {
    global $db;
    $stmt = $db->prepare("SELECT p.*, c.name as category_name FROM products p 
                         LEFT JOIN categories c ON p.category_id = c.id 
                         WHERE p.id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function addProduct($name, $category_id, $price, $quantity, $image_url, $description) {
    global $db;
    $stmt = $db->prepare("INSERT INTO products (name, category_id, price, quantity, image_url, description) 
                         VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sidiss", $name, $category_id, $price, $quantity, $image_url, $description);
    return $stmt->execute();
}

function updateProduct($id, $name, $category_id, $price, $quantity, $image_url, $description) {
    global $db;
    $stmt = $db->prepare("UPDATE products SET name=?, category_id=?, price=?, quantity=?, image_url=?, description=? 
                         WHERE id=?");
    $stmt->bind_param("sidissi", $name, $category_id, $price, $quantity, $image_url, $description, $id);
    return $stmt->execute();
}

function deleteProduct($id) {
    global $db;
    $stmt = $db->prepare("DELETE FROM products WHERE id=?");
    $stmt->bind_param("i", $id);
    return $stmt->execute();
}

function updateProductQuantity($id, $quantity_change) {
    global $db;
    $stmt = $db->prepare("UPDATE products SET quantity = quantity + ? WHERE id = ?");
    $stmt->bind_param("ii", $quantity_change, $id);
    return $stmt->execute();
}

// Category functions
function getAllCategories() {
    global $db;
    return $db->query("SELECT * FROM categories ORDER BY name");
}

function getCategoryById($id) {
    global $db;
    $stmt = $db->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function addCategory($name, $description) {
    global $db;
    $stmt = $db->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
    $stmt->bind_param("ss", $name, $description);
    return $stmt->execute();
}

function updateCategory($id, $name, $description) {
    global $db;
    $stmt = $db->prepare("UPDATE categories SET name=?, description=? WHERE id=?");
    $stmt->bind_param("ssi", $name, $description, $id);
    return $stmt->execute();
}

function deleteCategory($id) {
    global $db;
    // Check if category has products
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        return false; // Cannot delete category with products
    }
    
    $stmt = $db->prepare("DELETE FROM categories WHERE id=?");
    $stmt->bind_param("i", $id);
    return $stmt->execute();
}

// Customer functions
function getAllCustomers() {
    global $db;
    return $db->query("SELECT * FROM customers ORDER BY name");
}

function addCustomer($name, $contact, $email, $address) {
    global $db;
    $stmt = $db->prepare("INSERT INTO customers (name, contact, email, address) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $contact, $email, $address);
    if ($stmt->execute()) {
        return $db->insertId();
    }
    return false;
}

function getCustomerByContact($contact) {
    global $db;
    $stmt = $db->prepare("SELECT * FROM customers WHERE contact = ?");
    $stmt->bind_param("s", $contact);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Bill functions
function generateBillNumber() {
    global $db;
    $result = $db->query("SELECT COUNT(*) as count FROM bills");
    $row = $result->fetch_assoc();
    $count = $row['count'] + 1;
    return 'INF-' . str_pad($count, 5, '0', STR_PAD_LEFT);
}

function createBill($customer_name, $customer_contact, $customer_email, $customer_address, $items, $total) {
    global $db;
    
    $db->getConnection()->begin_transaction();
    
    try {
        // Check if customer exists, if not create new
        $customer = getCustomerByContact($customer_contact);
        if (!$customer) {
            $customer_id = addCustomer($customer_name, $customer_contact, $customer_email, $customer_address);
        } else {
            $customer_id = $customer['id'];
        }
        
        // Generate bill number
        $bill_number = generateBillNumber();
        
        // Create bill
        $stmt = $db->prepare("INSERT INTO bills (bill_number, customer_id, customer_name, customer_contact, customer_email, customer_address, total) 
                             VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sissssd", $bill_number, $customer_id, $customer_name, $customer_contact, $customer_email, $customer_address, $total);
        $stmt->execute();
        
        $bill_id = $db->insertId();
        
        // Add bill items and update inventory
        foreach ($items as $item) {
            // Add bill item
            $stmt = $db->prepare("INSERT INTO bill_items (bill_id, product_id, product_name, quantity, price, subtotal) 
                                 VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iisidd", $bill_id, $item['product_id'], $item['product_name'], $item['quantity'], $item['price'], $item['subtotal']);
            $stmt->execute();
            
            // Update product quantity
            updateProductQuantity($item['product_id'], -$item['quantity']);
        }
        
        $db->getConnection()->commit();
        return $bill_id;
        
    } catch (Exception $e) {
        $db->getConnection()->rollback();
        return false;
    }
}

function getBillById($id) {
    global $db;
    $stmt = $db->prepare("SELECT * FROM bills WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $bill = $result->fetch_assoc();
    
    if ($bill) {
        // Get bill items
        $stmt = $db->prepare("SELECT * FROM bill_items WHERE bill_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $items_result = $stmt->get_result();
        
        $bill['items'] = [];
        while ($item = $items_result->fetch_assoc()) {
            $bill['items'][] = $item;
        }
    }
    
    return $bill;
}

function getAllBills() {
    global $db;
    return $db->query("SELECT * FROM bills ORDER BY created_at DESC");
}

// Dashboard functions
function getDashboardStats() {
    global $db;
    
    $stats = [];
    
    // Total products
    $result = $db->query("SELECT COUNT(*) as count FROM products");
    $stats['total_products'] = $result->fetch_assoc()['count'];
    
    // Total customers
    $result = $db->query("SELECT COUNT(*) as count FROM customers");
    $stats['total_customers'] = $result->fetch_assoc()['count'];
    
    // Total categories
    $result = $db->query("SELECT COUNT(*) as count FROM categories");
    $stats['total_categories'] = $result->fetch_assoc()['count'];
    
    // Total bills
    $result = $db->query("SELECT COUNT(*) as count FROM bills");
    $stats['total_bills'] = $result->fetch_assoc()['count'];
    
    // Low stock products (quantity < 10)
    $result = $db->query("SELECT COUNT(*) as count FROM products WHERE quantity < 10");
    $stats['low_stock_products'] = $result->fetch_assoc()['count'];
    
    // Today's sales
    $today = date('Y-m-d');
    $result = $db->query("SELECT SUM(total) as total FROM bills WHERE DATE(created_at) = '$today'");
    $row = $result->fetch_assoc();
    $stats['today_sales'] = $row['total'] ? $row['total'] : 0;
    
    return $stats;
}

// Utility functions
function formatCurrency($amount) {
    return CURRENCY . ' ' . number_format($amount, 2);
}

function formatDate($date) {
    return date('Y-m-d H:i:s', strtotime($date));
}

function redirectTo($url) {
    header("Location: $url");
    exit();
}

function showAlert($message, $type = 'success') {
    $_SESSION['alert'] = ['message' => $message, 'type' => $type];
}

function displayAlert() {
    if (isset($_SESSION['alert'])) {
        $alert = $_SESSION['alert'];
        echo "<div class='alert alert-{$alert['type']}'>{$alert['message']}</div>";
        unset($_SESSION['alert']);
    }
}
?>