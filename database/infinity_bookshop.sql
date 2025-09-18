-- INFINITY Bookshop Database Structure
-- For WAMP Server (MySQL)

-- Create database
CREATE DATABASE IF NOT EXISTS infinity_bookshop;
USE infinity_bookshop;

-- Admin users table
CREATE TABLE admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Categories table
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Products table
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    category_id INT,
    price DECIMAL(10,2) NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    image_url TEXT,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- Customers table
CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    contact VARCHAR(20) NOT NULL,
    email VARCHAR(100),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bills table
CREATE TABLE bills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bill_number VARCHAR(20) UNIQUE NOT NULL,
    customer_id INT,
    customer_name VARCHAR(100) NOT NULL,
    customer_contact VARCHAR(20) NOT NULL,
    customer_email VARCHAR(100),
    customer_address TEXT,
    total DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id)
);

-- Bill items table
CREATE TABLE bill_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bill_id INT,
    product_id INT,
    product_name VARCHAR(200) NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (bill_id) REFERENCES bills(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Insert admin user (password: admin123 - hashed)
INSERT INTO admin_users (username, password) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Insert categories
INSERT INTO categories (name, description) VALUES
('School Books', 'Academic textbooks and educational materials'),
('Stationery', 'Pens, pencils, notebooks and office supplies'),
('Educational Materials', 'Learning aids and educational resources'),
('Art Supplies', 'Drawing and creative materials');

-- Insert sample products
INSERT INTO products (name, category_id, price, quantity, image_url, description) VALUES
('Mathematics Textbook Grade 10', 1, 850.00, 50, 'https://images.unsplash.com/photo-1497633762265-9d179a990aa6?crop=entropy&cs=srgb&fm=jpg&ixid=M3w3NDQ2Mzl8MHwxfHNlYXJjaHwxfHxzY2hvb2wlMjBib29rc3xlbnwwfHx8fDE3NTgxOTc4Nzh8MA&ixlib=rb-4.1.0&q=85', 'Comprehensive mathematics textbook for grade 10 students'),
('English Grammar Workbook', 1, 650.00, 75, 'https://images.unsplash.com/photo-1565022536102-f7645c84354a?crop=entropy&cs=srgb&fm=jpg&ixid=M3w3NDQ2Mzl8MHwxfHNlYXJjaHwyfHxzY2hvb2wlMjBib29rc3xlbnwwfHx8fDE3NTgxOTc4Nzh8MA&ixlib=rb-4.1.0&q=85', 'Interactive English grammar exercises and activities'),
('Science Laboratory Manual', 1, 950.00, 30, 'https://images.unsplash.com/photo-1485322551133-3a4c27a9d925?crop=entropy&cs=srgb&fm=jpg&ixid=M3w3NDQ2Mzl8MHwxfHNlYXJjaHwzfHxzY2hvb2wlMjBib29rc3xlbnwwfHx8fDE3NTgxOTc4Nzh8MA&ixlib=rb-4.1.0&q=85', 'Practical science experiments and laboratory procedures'),
('History of Sri Lanka', 1, 750.00, 40, 'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?crop=entropy&cs=srgb&fm=jpg&ixid=M3w3NDQ2Mzl8MHwxfHNlYXJjaHw0fHxzY2hvb2wlMjBib29rc3xlbnwwfHx8fDE3NTgxOTc4Nzh8MA&ixlib=rb-4.1.0&q=85', 'Comprehensive history book covering Sri Lankan heritage'),
('Blue Ballpoint Pens (Pack of 10)', 2, 200.00, 100, 'https://images.unsplash.com/photo-1631173716529-fd1696a807b0?crop=entropy&cs=srgb&fm=jpg&ixid=M3w3NDk1ODB8MHwxfHNlYXJjaHwxfHxzdGF0aW9uZXJ5fGVufDB8fHx8MTc1ODE5Nzg4M3ww&ixlib=rb-4.1.0&q=85', 'High-quality ballpoint pens for everyday writing'),
('HB Pencils (Pack of 12)', 2, 150.00, 120, 'https://images.unsplash.com/photo-1456735190827-d1262f71b8a3?crop=entropy&cs=srgb&fm=jpg&ixid=M3w3NDk1ODB8MHwxfHNlYXJjaHwyfHxzdGF0aW9uZXJ5fGVufDB8fHx8MTc1ODE5Nzg4M3ww&ixlib=rb-4.1.0&q=85', 'Standard HB pencils perfect for writing and drawing'),
('A4 Ruled Notebooks', 2, 300.00, 80, 'https://images.unsplash.com/photo-1513077202514-c511b41bd4c7?crop=entropy&cs=srgb&fm=jpg&ixid=M3w3NDk1ODB8MHwxfHNlYXJjaHwzfHxzdGF0aW9uZXJ5fGVufDB8fHx8MTc1ODE5Nzg4M3ww&ixlib=rb-4.1.0&q=85', '200-page ruled notebooks suitable for all subjects'),
('Geometry Set', 2, 450.00, 60, 'https://images.unsplash.com/photo-1510070009289-b5bc34383727?crop=entropy&cs=srgb&fm=jpg&ixid=M3w3NDk1ODB8MHwxfHNlYXJjaHw0fHxzdGF0aW9uZXJ5fGVufDB8fHx8MTc1ODE5Nzg4M3ww&ixlib=rb-4.1.0&q=85', 'Complete geometry set with compass, protractor, and rulers'),
('Educational World Map', 3, 1200.00, 25, 'https://images.unsplash.com/photo-1497633762265-9d179a990aa6?crop=entropy&cs=srgb&fm=jpg&ixid=M3w3NDQ2Mzl8MHwxfHNlYXJjaHwxfHxzY2hvb2wlMjBib29rc3xlbnwwfHx8fDE3NTgxOTc4Nzh8MA&ixlib=rb-4.1.0&q=85', 'Large educational world map for classroom use'),
('Calculator Scientific', 3, 2500.00, 35, 'https://images.unsplash.com/photo-1565022536102-f7645c84354a?crop=entropy&cs=srgb&fm=jpg&ixid=M3w3NDQ2Mzl8MHwxfHNlYXJjaHwyfHxzY2hvb2wlMjBib29rc3xlbnwwfHx8fDE3NTgxOTc4Nzh8MA&ixlib=rb-4.1.0&q=85', 'Advanced scientific calculator for mathematics and science'),
('Colored Pencils Set (24 colors)', 4, 800.00, 45, 'https://images.unsplash.com/photo-1631173716529-fd1696a807b0?crop=entropy&cs=srgb&fm=jpg&ixid=M3w3NDk1ODB8MHwxfHNlYXJjaHwxfHxzdGF0aW9uZXJ5fGVufDB8fHx8MTc1ODE5Nzg4M3ww&ixlib=rb-4.1.0&q=85', 'Professional colored pencils for art and drawing'),
('Art Sketchbook A3', 4, 600.00, 30, 'https://images.unsplash.com/photo-1456735190827-d1262f71b8a3?crop=entropy&cs=srgb&fm=jpg&ixid=M3w3NDk1ODB8MHwxfHNlYXJjaHwyfHxzdGF0aW9uZXJ5fGVufDB8fHx8MTc1ODE5Nzg4M3ww&ixlib=rb-4.1.0&q=85', 'High-quality drawing paper for sketching and artwork');

-- Insert sample customers
INSERT INTO customers (name, contact, email, address) VALUES
('Amal Perera', '0771234567', 'amal@email.com', '123 Main Street, Colombo 07'),
('Nimal Silva', '0779876543', 'nimal@email.com', '456 Galle Road, Dehiwala'),
('Kamala Jayawardena', '0763456789', 'kamala@email.com', '789 Kandy Road, Peradeniya'),
('Sunil Fernando', '0785551234', 'sunil@email.com', '321 High Level Road, Nugegoda'),
('Priya Rajapaksa', '0771119999', 'priya@email.com', '654 Temple Road, Mount Lavinia');