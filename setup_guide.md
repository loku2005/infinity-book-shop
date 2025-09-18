# INFINITY Bookshop - WAMP Server Setup Guide

## 📋 Prerequisites

- **WAMP Server** (or XAMPP/LAMP) installed on your computer
- **Web browser** (Chrome, Firefox, etc.)
- **Text editor** (optional, for customization)

## 🚀 Installation Steps

### Step 1: Download and Extract
1. Download the INFINITY Bookshop files
2. Extract the files to your WAMP server directory:
   - **WAMP**: `C:\wamp64\www\infinity_bookshop\`
   - **XAMPP**: `C:\xampp\htdocs\infinity_bookshop\`

### Step 2: Start WAMP Server
1. Start your WAMP server
2. Ensure both **Apache** and **MySQL** services are running (green icons)
3. Click on WAMP icon and verify "Localhost" is accessible

### Step 3: Database Setup
1. Open **phpMyAdmin**: http://localhost/phpmyadmin
2. Click **"Import"** tab
3. Choose file: `database/infinity_bookshop.sql`
4. Click **"Go"** to import the database

**Alternative Method:**
1. Create new database named: `infinity_bookshop`
2. Copy content from `database/infinity_bookshop.sql`
3. Paste in SQL tab and execute

### Step 4: Configuration (Optional)
Edit `includes/config.php` if needed:
```php
// Database credentials (default WAMP settings)
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'infinity_bookshop');

// Base URL (adjust if using different folder name)
$base_url = 'http://localhost/infinity_bookshop/';
```

### Step 5: Access the System
1. **Customer Interface**: http://localhost/infinity_bookshop/
2. **Admin Panel**: http://localhost/infinity_bookshop/admin/

## 👤 Default Login Credentials

**Admin Access:**
- Username: `admin`
- Password: `admin123`

## 🎯 System Features

### For Customers (No Login Required):
- ✅ Browse product catalog with images
- ✅ View products by categories
- ✅ Add items to shopping cart
- ✅ Enter contact details and create bills
- ✅ Print professional invoices

### For Admin (Login Required):
- ✅ Dashboard with sales statistics
- ✅ Product management (Add/Edit/Delete)
- ✅ Category management
- ✅ Customer records management
- ✅ View all bills and sales reports
- ✅ Low stock alerts

## 📊 Sample Data Included

The system comes with:
- **4 Categories**: School Books, Stationery, Educational Materials, Art Supplies
- **12 Products**: Mathematics books, stationery items, calculators, etc.
- **5 Sample Customers**: With realistic contact details
- **1 Admin User**: admin/admin123

## 🛠️ Troubleshooting

### Database Connection Issues:
1. Verify MySQL is running in WAMP
2. Check database credentials in `includes/config.php`
3. Ensure `infinity_bookshop` database exists

### Pages Not Loading:
1. Check Apache is running
2. Verify file path: `http://localhost/infinity_bookshop/`
3. Clear browser cache

### Images Not Showing:
- Images use external URLs (Unsplash)
- Requires internet connection for product images
- Admin can change image URLs in product management

### Permission Issues:
1. Right-click WAMP folder → Properties → Security
2. Give full control to "Everyone" or your user account

## 📁 File Structure

```
infinity_bookshop/
├── index.php                 # Customer interface
├── print_bill.php           # Invoice printing
├── database/
│   └── infinity_bookshop.sql # Database structure & data
├── includes/
│   ├── config.php           # Configuration
│   ├── database.php         # Database connection
│   └── functions.php        # Core functions
├── assets/
│   ├── css/
│   │   ├── style.css        # Customer styles
│   │   └── admin.css        # Admin styles
│   └── js/
│       └── script.js        # Frontend JavaScript
├── admin/
│   ├── index.php           # Admin login
│   ├── dashboard.php       # Admin dashboard
│   ├── products.php        # Product management
│   └── includes/
│       ├── header.php      # Admin header
│       └── sidebar.php     # Admin navigation
└── setup_guide.md         # This file
```

## 🔧 Customization Options

### Changing Company Information:
Edit `includes/config.php`:
```php
define('SITE_NAME', 'Your Bookshop Name');
define('SITE_DESCRIPTION', 'Your Description');
define('CURRENCY', 'Rs.');
```

### Adding More Products:
1. Login to admin panel
2. Go to Products → Add Product
3. Fill in details and image URL

### Modifying Design:
- Customer styles: `assets/css/style.css`
- Admin styles: `assets/css/admin.css`

## 📞 Support

For technical support or customization requests:
- Check configuration settings
- Verify WAMP server is running properly
- Ensure database is imported correctly

## 🔒 Security Notes

**For Production Use:**
1. Change default admin password
2. Use strong database passwords
3. Enable PHP security settings
4. Regular backups of database

---

**🎉 Congratulations!** Your INFINITY Bookshop billing system is ready to use!

Visit: http://localhost/infinity_bookshop/ to start using the system.