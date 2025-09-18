<nav class="navbar navbar-dark bg-dark fixed-top">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
            <div class="logo-icon me-3">
                <i class="fas fa-book"></i>
            </div>
            <div>
                <strong>INFINITY</strong>
                <small class="d-block text-muted">Admin Panel</small>
            </div>
        </a>
        
        <div class="d-flex align-items-center">
            <div class="dropdown">
                <button class="btn btn-outline-light dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown">
                    <i class="fas fa-user-circle me-2"></i>
                    <?php echo $_SESSION['admin_username']; ?>
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="../index.php" target="_blank">
                        <i class="fas fa-eye me-2"></i>View Site
                    </a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="index.php?logout=1">
                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                    </a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<style>
.navbar-brand .logo-icon {
    background: linear-gradient(45deg, #007bff, #0056b3);
    border-radius: 8px;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}
</style>