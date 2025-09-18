// INFINITY Bookshop Frontend JavaScript
// Shopping cart and UI functionality

class InfinityBookshop {
    constructor() {
        this.cart = [];
        this.currentCategory = '';
        this.products = [];
        
        this.init();
    }
    
    init() {
        this.loadProducts();
        this.bindEvents();
        this.updateCartDisplay();
    }
    
    bindEvents() {
        // Category navigation
        document.querySelectorAll('.category-item').forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                this.selectCategory(e.target);
            });
        });
        
        // Checkout form submission
        document.getElementById('confirm-order-btn').addEventListener('click', () => {
            this.createBill();
        });
        
        // Print bill button
        document.getElementById('print-bill-btn').addEventListener('click', () => {
            this.printBill();
        });
        
        // Modal events
        document.getElementById('checkoutModal').addEventListener('show.bs.modal', () => {
            this.populateCheckoutSummary();
        });
    }
    
    selectCategory(element) {
        // Update active category
        document.querySelectorAll('.category-item').forEach(item => {
            item.classList.remove('active');
        });
        element.classList.add('active');
        
        // Get category data
        this.currentCategory = element.dataset.category;
        const categoryName = element.textContent.trim();
        
        // Update title
        document.getElementById('category-title').textContent = categoryName;
        
        // Load products for this category
        this.loadProducts(this.currentCategory);
    }
    
    async loadProducts(categoryId = '') {
        const loadingSpinner = document.getElementById('loading-spinner');
        const productsContainer = document.getElementById('products-container');
        
        // Show loading
        loadingSpinner.style.display = 'block';
        productsContainer.innerHTML = '';
        
        try {
            const formData = new FormData();
            formData.append('action', 'get_products');
            if (categoryId) {
                formData.append('category_id', categoryId);
            }
            
            const response = await fetch('index.php', {
                method: 'POST',
                body: formData
            });
            
            const products = await response.json();
            this.products = products;
            
            // Hide loading
            loadingSpinner.style.display = 'none';
            
            // Display products
            this.displayProducts(products);
            
            // Update product count
            document.getElementById('product-count').textContent = products.length;
            
        } catch (error) {
            console.error('Error loading products:', error);
            loadingSpinner.style.display = 'none';
            productsContainer.innerHTML = '<div class="col-12"><div class="alert alert-danger">Error loading products. Please try again.</div></div>';
        }
    }
    
    displayProducts(products) {
        const container = document.getElementById('products-container');
        
        if (products.length === 0) {
            container.innerHTML = `
                <div class="col-12">
                    <div class="empty-state">
                        <i class="fas fa-box-open"></i>
                        <h5>No products found</h5>
                        <p>There are no products in this category.</p>
                    </div>
                </div>
            `;
            return;
        }
        
        const productsHTML = products.map(product => {
            const stockClass = product.quantity < 10 ? 'stock-low' : 'stock-good';
            const stockText = product.quantity < 10 ? `Low Stock: ${product.quantity}` : `Stock: ${product.quantity}`;
            const isOutOfStock = product.quantity === 0;
            
            return `
                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 fade-in">
                    <div class="card product-card h-100">
                        <div class="product-image">
                            ${product.image_url ? 
                                `<img src="${product.image_url}" alt="${product.name}" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                 <div style="display:none;" class="w-100 h-100 d-flex align-items-center justify-content-center">
                                     <i class="fas fa-book fa-2x"></i>
                                 </div>` : 
                                `<i class="fas fa-book fa-2x"></i>`
                            }
                        </div>
                        <div class="product-info">
                            <h6 class="product-title">${product.name}</h6>
                            <div class="product-category">${product.category_name || 'Uncategorized'}</div>
                            <p class="product-description">${product.description || ''}</p>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="product-price">Rs. ${parseFloat(product.price).toFixed(2)}</div>
                                <span class="badge stock-badge ${stockClass}">${stockText}</span>
                            </div>
                            <button class="btn btn-primary add-to-cart-btn ${isOutOfStock ? 'disabled' : ''}" 
                                    onclick="bookshop.addToCart(${product.id})" 
                                    ${isOutOfStock ? 'disabled' : ''}>
                                <i class="fas fa-shopping-cart me-2"></i>
                                ${isOutOfStock ? 'Out of Stock' : 'Add to Cart'}
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
        
        container.innerHTML = productsHTML;
    }
    
    addToCart(productId) {
        const product = this.products.find(p => p.id == productId);
        if (!product || product.quantity === 0) {
            this.showAlert('Product is out of stock!', 'warning');
            return;
        }
        
        // Check if product already in cart
        const existingItem = this.cart.find(item => item.id == productId);
        
        if (existingItem) {
            if (existingItem.quantity < product.quantity) {
                existingItem.quantity += 1;
                existingItem.subtotal = existingItem.quantity * existingItem.price;
            } else {
                this.showAlert('Cannot add more items. Insufficient stock!', 'warning');
                return;
            }
        } else {
            this.cart.push({
                id: product.id,
                name: product.name,
                price: parseFloat(product.price),
                quantity: 1,
                subtotal: parseFloat(product.price),
                image_url: product.image_url,
                max_quantity: product.quantity
            });
        }
        
        this.updateCartDisplay();
        this.showAlert('Product added to cart!', 'success');
        
        // Show cart sidebar briefly
        const offcanvas = new bootstrap.Offcanvas(document.getElementById('cartSidebar'));
        offcanvas.show();
        setTimeout(() => offcanvas.hide(), 2000);
    }
    
    updateCartQuantity(productId, newQuantity) {
        const item = this.cart.find(item => item.id == productId);
        if (!item) return;
        
        if (newQuantity <= 0) {
            this.removeFromCart(productId);
        } else if (newQuantity <= item.max_quantity) {
            item.quantity = newQuantity;
            item.subtotal = item.quantity * item.price;
            this.updateCartDisplay();
        } else {
            this.showAlert('Cannot add more items. Insufficient stock!', 'warning');
        }
    }
    
    removeFromCart(productId) {
        this.cart = this.cart.filter(item => item.id != productId);
        this.updateCartDisplay();
        this.showAlert('Item removed from cart', 'info');
    }
    
    updateCartDisplay() {
        const cartCount = document.getElementById('cart-count');
        const cartItems = document.getElementById('cart-items');
        const cartTotal = document.getElementById('cart-total');
        const checkoutBtn = document.getElementById('checkout-btn');
        
        // Update cart count
        const totalItems = this.cart.reduce((sum, item) => sum + item.quantity, 0);
        cartCount.textContent = totalItems;
        
        // Update cart total
        const total = this.cart.reduce((sum, item) => sum + item.subtotal, 0);
        cartTotal.textContent = `Rs. ${total.toFixed(2)}`;
        
        // Enable/disable checkout button
        checkoutBtn.disabled = this.cart.length === 0;
        
        // Update cart items display
        if (this.cart.length === 0) {
            cartItems.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-shopping-cart"></i>
                    <h6>Your cart is empty</h6>
                    <p>Add products to get started</p>
                </div>
            `;
        } else {
            const cartHTML = this.cart.map(item => `
                <div class="cart-item">
                    <div class="d-flex align-items-center">
                        <div class="cart-item-image">
                            ${item.image_url ? 
                                `<img src="${item.image_url}" alt="${item.name}">` : 
                                `<i class="fas fa-book"></i>`
                            }
                        </div>
                        <div class="cart-item-info">
                            <div class="cart-item-title">${item.name}</div>
                            <div class="cart-item-price">Rs. ${item.price.toFixed(2)} each</div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-between mt-2">
                        <div class="quantity-controls">
                            <button class="quantity-btn" onclick="bookshop.updateCartQuantity(${item.id}, ${item.quantity - 1})">
                                <i class="fas fa-minus"></i>
                            </button>
                            <input type="number" class="quantity-input" value="${item.quantity}" 
                                   onchange="bookshop.updateCartQuantity(${item.id}, parseInt(this.value))"
                                   min="1" max="${item.max_quantity}">
                            <button class="quantity-btn" onclick="bookshop.updateCartQuantity(${item.id}, ${item.quantity + 1})">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        <div class="d-flex align-items-center">
                            <strong>Rs. ${item.subtotal.toFixed(2)}</strong>
                            <button class="btn btn-sm text-danger ms-2" onclick="bookshop.removeFromCart(${item.id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `).join('');
            
            cartItems.innerHTML = cartHTML;
        }
    }
    
    populateCheckoutSummary() {
        const checkoutItems = document.getElementById('checkout-items');
        const checkoutTotal = document.getElementById('checkout-total');
        
        const total = this.cart.reduce((sum, item) => sum + item.subtotal, 0);
        checkoutTotal.textContent = `Rs. ${total.toFixed(2)}`;
        
        const itemsHTML = this.cart.map(item => `
            <div class="d-flex justify-content-between mb-2">
                <span>${item.name} × ${item.quantity}</span>
                <span>Rs. ${item.subtotal.toFixed(2)}</span>
            </div>
        `).join('');
        
        checkoutItems.innerHTML = itemsHTML;
    }
    
    async createBill() {
        const form = document.getElementById('checkout-form');
        const formData = new FormData(form);
        
        // Validate required fields
        if (!formData.get('customer_name') || !formData.get('customer_contact')) {
            this.showAlert('Please fill in all required fields', 'danger');
            return;
        }
        
        if (this.cart.length === 0) {
            this.showAlert('Your cart is empty', 'warning');
            return;
        }
        
        const confirmBtn = document.getElementById('confirm-order-btn');
        confirmBtn.disabled = true;
        confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creating Bill...';
        
        try {
            // Prepare cart items for backend
            const cartItems = this.cart.map(item => ({
                product_id: item.id,
                product_name: item.name,
                quantity: item.quantity,
                price: item.price,
                subtotal: item.subtotal
            }));
            
            const total = this.cart.reduce((sum, item) => sum + item.subtotal, 0);
            
            // Prepare form data
            const billData = new FormData();
            billData.append('action', 'create_bill');
            billData.append('customer_name', formData.get('customer_name'));
            billData.append('customer_contact', formData.get('customer_contact'));
            billData.append('customer_email', formData.get('customer_email'));
            billData.append('customer_address', formData.get('customer_address'));
            billData.append('cart_items', JSON.stringify(cartItems));
            billData.append('total', total);
            
            const response = await fetch('index.php', {
                method: 'POST',
                body: billData
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Close checkout modal
                const checkoutModal = bootstrap.Modal.getInstance(document.getElementById('checkoutModal'));
                checkoutModal.hide();
                
                // Clear cart
                this.cart = [];
                this.updateCartDisplay();
                
                // Show success modal
                this.currentBillId = result.bill_id;
                document.getElementById('bill-number').textContent = `INF-${String(result.bill_id).padStart(5, '0')}`;
                
                const successModal = new bootstrap.Modal(document.getElementById('billSuccessModal'));
                successModal.show();
                
                // Reload products to update stock
                this.loadProducts(this.currentCategory);
                
            } else {
                this.showAlert(result.message || 'Failed to create bill', 'danger');
            }
            
        } catch (error) {
            console.error('Error creating bill:', error);
            this.showAlert('Error creating bill. Please try again.', 'danger');
        } finally {
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = '<i class="fas fa-check me-2"></i>Create Bill';
        }
    }
    
    printBill() {
        if (!this.currentBillId) return;
        
        // Open print window
        const printWindow = window.open(`print_bill.php?id=${this.currentBillId}`, '_blank', 'width=800,height=600');
        printWindow.focus();
    }
    
    showAlert(message, type = 'info') {
        // Create alert element
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        alertDiv.style.cssText = 'top: 100px; right: 20px; z-index: 9999; min-width: 300px;';
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(alertDiv);
        
        // Auto remove after 3 seconds
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 3000);
    }
}

// Initialize the application when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.bookshop = new InfinityBookshop();
});

// Utility functions
function formatCurrency(amount) {
    return `Rs. ${parseFloat(amount).toFixed(2)}`;
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Add smooth scrolling for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Add loading states for buttons
document.addEventListener('click', function(e) {
    if (e.target.matches('.btn[data-loading]') || e.target.closest('.btn[data-loading]')) {
        const btn = e.target.matches('.btn') ? e.target : e.target.closest('.btn');
        const originalText = btn.innerHTML;
        
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Loading...';
        
        // Restore button after 2 seconds (or handle in your async function)
        setTimeout(() => {
            btn.disabled = false;
            btn.innerHTML = originalText;
        }, 2000);
    }
});