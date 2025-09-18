<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                    <i class="fas fa-tachometer-alt me-2"></i>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>" href="products.php">
                    <i class="fas fa-book me-2"></i>
                    Products
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>" href="categories.php">
                    <i class="fas fa-folder me-2"></i>
                    Categories
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'customers.php' ? 'active' : ''; ?>" href="customers.php">
                    <i class="fas fa-users me-2"></i>
                    Customers
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'bills.php' ? 'active' : ''; ?>" href="bills.php">
                    <i class="fas fa-file-invoice me-2"></i>
                    Bills
                </a>
            </li>
        </ul>

        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>Quick Actions</span>
        </h6>
        <ul class="nav flex-column mb-2">
            <li class="nav-item">
                <a class="nav-link text-muted" href="../index.php" target="_blank">
                    <i class="fas fa-external-link-alt me-2"></i>
                    View Site
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-muted" href="reports.php">
                    <i class="fas fa-chart-bar me-2"></i>
                    Reports
                </a>
            </li>
        </ul>
    </div>
</nav>

<style>
.sidebar {
    position: fixed;
    top: 0;
    bottom: 0;
    left: 0;
    z-index: 100;
    padding: 56px 0 0;
    box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
}

.sidebar-heading {
    font-size: .75rem;
    text-transform: uppercase;
}

.sidebar .nav-link {
    font-weight: 500;
    color: #333;
    border-radius: 0.375rem;
    margin: 0.125rem 0.75rem;
    padding: 0.75rem 1rem;
    transition: all 0.3s ease;
}

.sidebar .nav-link:hover {
    color: #007bff;
    background-color: #f8f9fa;
}

.sidebar .nav-link.active {
    color: #007bff;
    background-color: #e3f2fd;
    border-left: 4px solid #007bff;
}

.sidebar .nav-link i {
    width: 16px;
    text-align: center;
}
</style>