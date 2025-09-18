<?php
require_once 'includes/functions.php';

// Handle AJAX requests
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'get_products':
            $category_id = isset($_POST['category_id']) && $_POST['category_id'] !== '' ? $_POST['category_id'] : null;
            
            if ($category_id) {
                $result = getProductsByCategory($category_id);
            } else {
                $result = getAllProducts();
            }
            
            $products = [];
            while ($row = $result->fetch_assoc()) {
                $products[] = $row;
            }
            
            echo json_encode($products);
            exit;
            
        case 'create_bill':
            $customer_name = $_POST['customer_name'];
            $customer_contact = $_POST['customer_contact'];
            $customer_email = $_POST['customer_email'] ?? '';
            $customer_address = $_POST['customer_address'] ?? '';
            $cart_items = json_decode($_POST['cart_items'], true);
            $total = $_POST['total'];
            
            $bill_id = createBill($customer_name, $customer_contact, $customer_email, $customer_address, $cart_items, $total);
            
            if ($bill_id) {
                echo json_encode(['success' => true, 'bill_id' => $bill_id]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create bill']);
            }
            exit;
    }
}

// Get categories for navigation
$categories = getAllCategories();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - <?php echo SITE_DESCRIPTION; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Header -->
    <header class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <div class="logo-icon me-3">
                    <i class="fas fa-book fa-2x"></i>
                </div>
                <div>
                    <h3 class="mb-0">INFINITY</h3>
                    <small class="text-light"><?php echo SITE_DESCRIPTION; ?></small>
                </div>
            </a>
            
            <div class="d-flex align-items-center">
                <button class="btn btn-outline-light me-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#cartSidebar">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="badge bg-warning" id="cart-count">0</span>
                </button>
                <a href="admin/" class="btn btn-outline-light">
                    <i class="fas fa-user-shield"></i> Admin
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar Categories -->
            <div class="col-md-3 col-lg-2 p-0">
                <div class="category-sidebar bg-light border-end">
                    <div class="p-3 border-bottom">
                        <h6 class="text-muted mb-0">Categories</h6>
                    </div>
                    <div class="list-group list-group-flush">
                        <a href="#" class="list-group-item list-group-item-action category-item active" data-category="">
                            <i class="fas fa-th-large me-2"></i> All Products
                        </a>
                        <?php while ($category = $categories->fetch_assoc()): ?>
                        <a href="#" class="list-group-item list-group-item-action category-item" data-category="<?php echo $category['id']; ?>">
                            <i class="fas fa-folder me-2"></i> <?php echo htmlspecialchars($category['name']); ?>
                        </a>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>

            <!-- Products Area -->
            <div class="col-md-9 col-lg-10">
                <div class="p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 id="category-title">All Products</h2>
                        <div class="d-flex align-items-center">
                            <span class="text-muted me-3">Total: <span id="product-count">0</span> items</span>
                        </div>
                    </div>

                    <!-- Products Grid -->
                    <div id="products-container" class="row g-4">
                        <!-- Products will be loaded here via AJAX -->
                    </div>

                    <!-- Loading Spinner -->
                    <div id="loading-spinner" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cart Sidebar -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="cartSidebar">
        <div class="offcanvas-header bg-primary text-white">
            <h5 class="offcanvas-title">
                <i class="fas fa-shopping-cart me-2"></i>Shopping Cart
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body p-0">
            <div id="cart-items" class="p-3">
                <!-- Cart items will be added here -->
            </div>
            <div class="cart-footer bg-light border-top p-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <strong>Total: <span id="cart-total"><?php echo CURRENCY; ?> 0.00</span></strong>
                </div>
                <button class="btn btn-success w-100" id="checkout-btn" data-bs-toggle="modal" data-bs-target="#checkoutModal">
                    <i class="fas fa-credit-card me-2"></i>Proceed to Checkout
                </button>
            </div>
        </div>
    </div>

    <!-- Checkout Modal -->
    <div class="modal fade" id="checkoutModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-file-invoice me-2"></i>Customer Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="checkout-form">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Full Name *</label>
                                    <input type="text" class="form-control" name="customer_name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Contact Number *</label>
                                    <input type="text" class="form-control" name="customer_contact" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" class="form-control" name="customer_email">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Address</label>
                                    <textarea class="form-control" name="customer_address" rows="3"></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Order Summary -->
                        <div class="border-top pt-3">
                            <h6>Order Summary</h6>
                            <div id="checkout-items" class="mb-3">
                                <!-- Order items will be displayed here -->
                            </div>
                            <div class="d-flex justify-content-between">
                                <strong>Total Amount: <span id="checkout-total"><?php echo CURRENCY; ?> 0.00</span></strong>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="confirm-order-btn">
                        <i class="fas fa-check me-2"></i>Create Bill
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bill Success Modal -->
    <div class="modal fade" id="billSuccessModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-check-circle me-2"></i>Bill Created Successfully!
                    </h5>
                </div>
                <div class="modal-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-receipt fa-3x text-success"></i>
                    </div>
                    <h6>Your bill has been created successfully!</h6>
                    <p class="text-muted">Bill Number: <strong id="bill-number"></strong></p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-primary" id="print-bill-btn">
                        <i class="fas fa-print me-2"></i>Print Bill
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
</body>
</html>