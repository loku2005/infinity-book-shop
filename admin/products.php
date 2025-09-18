<?php
require_once '../includes/functions.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    redirectTo('index.php');
}

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                if (addProduct($_POST['name'], $_POST['category_id'], $_POST['price'], $_POST['quantity'], $_POST['image_url'], $_POST['description'])) {
                    showAlert('Product added successfully!', 'success');
                } else {
                    showAlert('Error adding product', 'danger');
                }
                break;
                
            case 'edit':
                if (updateProduct($_POST['id'], $_POST['name'], $_POST['category_id'], $_POST['price'], $_POST['quantity'], $_POST['image_url'], $_POST['description'])) {
                    showAlert('Product updated successfully!', 'success');
                } else {
                    showAlert('Error updating product', 'danger');
                }
                break;
                
            case 'delete':
                if (deleteProduct($_POST['id'])) {
                    showAlert('Product deleted successfully!', 'success');
                } else {
                    showAlert('Error deleting product', 'danger');
                }
                break;
        }
    }
}

// Get products and categories
$products = getAllProducts();
$categories = getAllCategories();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-book me-2"></i>Products Management
                    </h1>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#productModal" onclick="resetForm()">
                        <i class="fas fa-plus me-2"></i>Add Product
                    </button>
                </div>

                <?php displayAlert(); ?>

                <!-- Products Table -->
                <div class="card data-table">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Image</th>
                                        <th>Name</th>
                                        <th>Category</th>
                                        <th>Price</th>
                                        <th>Stock</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($product = $products->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div class="product-image">
                                                <?php if ($product['image_url']): ?>
                                                    <img src="<?php echo $product['image_url']; ?>" alt="<?php echo $product['name']; ?>">
                                                <?php else: ?>
                                                    <i class="fas fa-book"></i>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                            <br><small class="text-muted"><?php echo htmlspecialchars(substr($product['description'], 0, 50)); ?>...</small>
                                        </td>
                                        <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                        <td><strong><?php echo formatCurrency($product['price']); ?></strong></td>
                                        <td>
                                            <span class="badge <?php echo $product['quantity'] < 10 ? 'bg-warning' : 'bg-success'; ?>">
                                                <?php echo $product['quantity']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-warning action-btn" 
                                                    onclick="editProduct(<?php echo htmlspecialchars(json_encode($product)); ?>)"
                                                    data-bs-toggle="modal" data-bs-target="#productModal">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger action-btn" 
                                                    onclick="deleteProduct(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name']); ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Product Modal -->
    <div class="modal fade" id="productModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-book me-2"></i>
                        <span id="modal-title">Add New Product</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="productForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" id="form-action" value="add">
                        <input type="hidden" name="id" id="product-id">
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label class="form-label">Product Name *</label>
                                    <input type="text" class="form-control" name="name" id="product-name" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Category *</label>
                                    <select class="form-control" name="category_id" id="product-category" required>
                                        <option value="">Select Category</option>
                                        <?php 
                                        $categories->data_seek(0);
                                        while ($category = $categories->fetch_assoc()): 
                                        ?>
                                        <option value="<?php echo $category['id']; ?>"><?php echo $category['name']; ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Price (Rs.) *</label>
                                    <input type="number" step="0.01" class="form-control" name="price" id="product-price" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Stock Quantity *</label>
                                    <input type="number" class="form-control" name="quantity" id="product-quantity" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Image URL</label>
                            <input type="url" class="form-control" name="image_url" id="product-image">
                            <small class="text-muted">Enter a valid image URL</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" id="product-description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Product
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Form -->
    <form method="POST" id="deleteForm" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="delete-id">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function resetForm() {
            document.getElementById('productForm').reset();
            document.getElementById('form-action').value = 'add';
            document.getElementById('modal-title').textContent = 'Add New Product';
            document.getElementById('product-id').value = '';
        }
        
        function editProduct(product) {
            document.getElementById('form-action').value = 'edit';
            document.getElementById('modal-title').textContent = 'Edit Product';
            document.getElementById('product-id').value = product.id;
            document.getElementById('product-name').value = product.name;
            document.getElementById('product-category').value = product.category_id;
            document.getElementById('product-price').value = product.price;
            document.getElementById('product-quantity').value = product.quantity;
            document.getElementById('product-image').value = product.image_url;
            document.getElementById('product-description').value = product.description;
        }
        
        function deleteProduct(id, name) {
            if (confirm('Are you sure you want to delete "' + name + '"?')) {
                document.getElementById('delete-id').value = id;
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
</body>
</html>