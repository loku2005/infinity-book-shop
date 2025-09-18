#!/usr/bin/env python3
"""
INFINITY Bookshop Backend API Testing Suite
Tests all backend APIs for the billing system
"""

import requests
import json
import sys
from datetime import datetime
import time

# Configuration
BASE_URL = "https://bookstore-manager-1.preview.emergentagent.com/api"
ADMIN_USERNAME = "admin"
ADMIN_PASSWORD = "admin123"

class BookshopAPITester:
    def __init__(self):
        self.base_url = BASE_URL
        self.token = None
        self.headers = {"Content-Type": "application/json"}
        self.test_results = []
        
        # Test data storage
        self.test_category_id = None
        self.test_product_id = None
        self.test_customer_id = None
        self.test_bill_id = None
        
    def log_test(self, test_name, success, message, details=None):
        """Log test results"""
        result = {
            "test": test_name,
            "success": success,
            "message": message,
            "details": details,
            "timestamp": datetime.now().isoformat()
        }
        self.test_results.append(result)
        status = "✅ PASS" if success else "❌ FAIL"
        print(f"{status} {test_name}: {message}")
        if details and not success:
            print(f"   Details: {details}")
    
    def make_request(self, method, endpoint, data=None, use_auth=True):
        """Make HTTP request with proper headers"""
        url = f"{self.base_url}{endpoint}"
        headers = self.headers.copy()
        
        if use_auth and self.token:
            headers["Authorization"] = f"Bearer {self.token}"
        
        try:
            if method == "GET":
                response = requests.get(url, headers=headers, timeout=30)
            elif method == "POST":
                response = requests.post(url, headers=headers, json=data, timeout=30)
            elif method == "PUT":
                response = requests.put(url, headers=headers, json=data, timeout=30)
            elif method == "DELETE":
                response = requests.delete(url, headers=headers, timeout=30)
            else:
                raise ValueError(f"Unsupported method: {method}")
            
            return response
        except requests.exceptions.RequestException as e:
            return None, str(e)
    
    def test_sample_data_initialization(self):
        """Test sample data initialization"""
        print("\n=== Testing Sample Data Initialization ===")
        
        response = self.make_request("POST", "/init-data", use_auth=False)
        if response is None:
            self.log_test("Sample Data Init", False, "Request failed - connection error")
            return False
        
        if response.status_code in [200, 201]:
            try:
                data = response.json()
                if "message" in data:
                    self.log_test("Sample Data Init", True, f"Success: {data['message']}")
                    return True
            except:
                pass
        
        self.log_test("Sample Data Init", False, f"Failed with status {response.status_code}", response.text[:200])
        return False
    
    def test_admin_authentication(self):
        """Test admin login and JWT token generation"""
        print("\n=== Testing Admin Authentication ===")
        
        # Test login
        login_data = {
            "username": ADMIN_USERNAME,
            "password": ADMIN_PASSWORD
        }
        
        response = self.make_request("POST", "/auth/login", login_data, use_auth=False)
        if response is None:
            self.log_test("Admin Login", False, "Request failed - connection error")
            return False
        
        if response.status_code == 200:
            try:
                data = response.json()
                if "access_token" in data and "user" in data:
                    self.token = data["access_token"]
                    self.log_test("Admin Login", True, f"Login successful for user: {data['user']['username']}")
                    
                    # Test token validation by making an authenticated request
                    stats_response = self.make_request("GET", "/dashboard/stats")
                    if stats_response and stats_response.status_code == 200:
                        self.log_test("JWT Token Validation", True, "Token works for protected endpoints")
                        return True
                    else:
                        self.log_test("JWT Token Validation", False, "Token doesn't work for protected endpoints")
                        return False
                else:
                    self.log_test("Admin Login", False, "Missing access_token or user in response", data)
                    return False
            except json.JSONDecodeError:
                self.log_test("Admin Login", False, "Invalid JSON response", response.text[:200])
                return False
        else:
            self.log_test("Admin Login", False, f"Login failed with status {response.status_code}", response.text[:200])
            return False
    
    def test_category_management(self):
        """Test category CRUD operations"""
        print("\n=== Testing Category Management ===")
        
        if not self.token:
            self.log_test("Category CRUD", False, "No authentication token available")
            return False
        
        # Test GET categories
        response = self.make_request("GET", "/categories")
        if response and response.status_code == 200:
            try:
                categories = response.json()
                self.log_test("Get Categories", True, f"Retrieved {len(categories)} categories")
                
                # Store first category for later tests
                if categories:
                    self.test_category_id = categories[0]["id"]
            except:
                self.log_test("Get Categories", False, "Invalid JSON response")
                return False
        else:
            self.log_test("Get Categories", False, f"Failed with status {response.status_code if response else 'No response'}")
            return False
        
        # Test CREATE category
        new_category = {
            "name": "Test Category",
            "description": "Category created during testing"
        }
        
        response = self.make_request("POST", "/categories", new_category)
        if response and response.status_code == 200:
            try:
                created_category = response.json()
                test_category_id = created_category["id"]
                self.log_test("Create Category", True, f"Created category: {created_category['name']}")
                
                # Test UPDATE category
                updated_data = {
                    "name": "Updated Test Category",
                    "description": "Updated description"
                }
                
                response = self.make_request("PUT", f"/categories/{test_category_id}", updated_data)
                if response and response.status_code == 200:
                    self.log_test("Update Category", True, "Category updated successfully")
                else:
                    self.log_test("Update Category", False, f"Update failed with status {response.status_code if response else 'No response'}")
                
                # Test DELETE category
                response = self.make_request("DELETE", f"/categories/{test_category_id}")
                if response and response.status_code == 200:
                    self.log_test("Delete Category", True, "Category deleted successfully")
                else:
                    self.log_test("Delete Category", False, f"Delete failed with status {response.status_code if response else 'No response'}")
                
                return True
            except:
                self.log_test("Create Category", False, "Invalid JSON response")
                return False
        else:
            self.log_test("Create Category", False, f"Failed with status {response.status_code if response else 'No response'}")
            return False
    
    def test_product_management(self):
        """Test product CRUD operations"""
        print("\n=== Testing Product Management ===")
        
        if not self.token or not self.test_category_id:
            self.log_test("Product CRUD", False, "No authentication token or category ID available")
            return False
        
        # Test GET products
        response = self.make_request("GET", "/products")
        if response and response.status_code == 200:
            try:
                products = response.json()
                self.log_test("Get Products", True, f"Retrieved {len(products)} products")
                
                # Store first product for later tests
                if products:
                    self.test_product_id = products[0]["id"]
            except:
                self.log_test("Get Products", False, "Invalid JSON response")
                return False
        else:
            self.log_test("Get Products", False, f"Failed with status {response.status_code if response else 'No response'}")
            return False
        
        # Test CREATE product
        new_product = {
            "name": "Test Mathematics Book",
            "category_id": self.test_category_id,
            "price": 1250.0,
            "quantity": 25,
            "image_url": "https://images.unsplash.com/photo-1497633762265-9d179a990aa6",
            "description": "Test mathematics textbook for grade 11"
        }
        
        response = self.make_request("POST", "/products", new_product)
        if response and response.status_code == 200:
            try:
                created_product = response.json()
                test_product_id = created_product["id"]
                self.log_test("Create Product", True, f"Created product: {created_product['name']}")
                
                # Test UPDATE product
                updated_data = {
                    "name": "Updated Test Mathematics Book",
                    "price": 1350.0,
                    "quantity": 30
                }
                
                response = self.make_request("PUT", f"/products/{test_product_id}", updated_data)
                if response and response.status_code == 200:
                    self.log_test("Update Product", True, "Product updated successfully")
                else:
                    self.log_test("Update Product", False, f"Update failed with status {response.status_code if response else 'No response'}")
                
                # Test DELETE product
                response = self.make_request("DELETE", f"/products/{test_product_id}")
                if response and response.status_code == 200:
                    self.log_test("Delete Product", True, "Product deleted successfully")
                else:
                    self.log_test("Delete Product", False, f"Delete failed with status {response.status_code if response else 'No response'}")
                
                return True
            except:
                self.log_test("Create Product", False, "Invalid JSON response")
                return False
        else:
            self.log_test("Create Product", False, f"Failed with status {response.status_code if response else 'No response'}")
            return False
    
    def test_customer_management(self):
        """Test customer CRUD operations"""
        print("\n=== Testing Customer Management ===")
        
        if not self.token:
            self.log_test("Customer CRUD", False, "No authentication token available")
            return False
        
        # Test GET customers
        response = self.make_request("GET", "/customers")
        if response and response.status_code == 200:
            try:
                customers = response.json()
                self.log_test("Get Customers", True, f"Retrieved {len(customers)} customers")
                
                # Store first customer for later tests
                if customers:
                    self.test_customer_id = customers[0]["id"]
            except:
                self.log_test("Get Customers", False, "Invalid JSON response")
                return False
        else:
            self.log_test("Get Customers", False, f"Failed with status {response.status_code if response else 'No response'}")
            return False
        
        # Test CREATE customer
        new_customer = {
            "name": "Sandun Wickramasinghe",
            "contact": "0771234567",
            "email": "sandun@email.com",
            "address": "123 Test Street, Colombo 05"
        }
        
        response = self.make_request("POST", "/customers", new_customer)
        if response and response.status_code == 200:
            try:
                created_customer = response.json()
                test_customer_id = created_customer["id"]
                self.log_test("Create Customer", True, f"Created customer: {created_customer['name']}")
                
                # Test UPDATE customer
                updated_data = {
                    "name": "Sandun Wickramasinghe Updated",
                    "contact": "0779876543",
                    "email": "sandun.updated@email.com",
                    "address": "456 Updated Street, Colombo 07"
                }
                
                response = self.make_request("PUT", f"/customers/{test_customer_id}", updated_data)
                if response and response.status_code == 200:
                    self.log_test("Update Customer", True, "Customer updated successfully")
                else:
                    self.log_test("Update Customer", False, f"Update failed with status {response.status_code if response else 'No response'}")
                
                # Test DELETE customer
                response = self.make_request("DELETE", f"/customers/{test_customer_id}")
                if response and response.status_code == 200:
                    self.log_test("Delete Customer", True, "Customer deleted successfully")
                else:
                    self.log_test("Delete Customer", False, f"Delete failed with status {response.status_code if response else 'No response'}")
                
                return True
            except:
                self.log_test("Create Customer", False, "Invalid JSON response")
                return False
        else:
            self.log_test("Create Customer", False, f"Failed with status {response.status_code if response else 'No response'}")
            return False
    
    def test_bill_generation(self):
        """Test bill creation and inventory updates"""
        print("\n=== Testing Bill Generation ===")
        
        if not self.token or not self.test_customer_id or not self.test_product_id:
            self.log_test("Bill Generation", False, "Missing required IDs for bill creation")
            return False
        
        # Get current product quantity before bill creation
        response = self.make_request("GET", "/products")
        if not response or response.status_code != 200:
            self.log_test("Bill Generation", False, "Could not retrieve products for inventory check")
            return False
        
        products = response.json()
        target_product = None
        for product in products:
            if product["quantity"] >= 2:  # Need at least 2 items in stock
                target_product = product
                break
        
        if not target_product:
            self.log_test("Bill Generation", False, "No products with sufficient stock for testing")
            return False
        
        original_quantity = target_product["quantity"]
        
        # Test CREATE bill
        bill_data = {
            "customer_id": self.test_customer_id,
            "items": [
                {
                    "product_id": target_product["id"],
                    "quantity": 2
                }
            ]
        }
        
        response = self.make_request("POST", "/bills", bill_data)
        if response and response.status_code == 200:
            try:
                created_bill = response.json()
                self.test_bill_id = created_bill["id"]
                self.log_test("Create Bill", True, f"Created bill: {created_bill['bill_number']} for total: Rs.{created_bill['total']}")
                
                # Verify inventory update
                response = self.make_request("GET", "/products")
                if response and response.status_code == 200:
                    updated_products = response.json()
                    updated_product = None
                    for product in updated_products:
                        if product["id"] == target_product["id"]:
                            updated_product = product
                            break
                    
                    if updated_product and updated_product["quantity"] == original_quantity - 2:
                        self.log_test("Inventory Update", True, f"Product quantity correctly updated from {original_quantity} to {updated_product['quantity']}")
                    else:
                        self.log_test("Inventory Update", False, f"Product quantity not updated correctly. Expected: {original_quantity - 2}, Got: {updated_product['quantity'] if updated_product else 'N/A'}")
                else:
                    self.log_test("Inventory Update", False, "Could not verify inventory update")
                
                # Test GET bill by ID
                response = self.make_request("GET", f"/bills/{self.test_bill_id}")
                if response and response.status_code == 200:
                    self.log_test("Get Bill by ID", True, "Successfully retrieved bill by ID")
                else:
                    self.log_test("Get Bill by ID", False, f"Failed to get bill by ID with status {response.status_code if response else 'No response'}")
                
                # Test GET all bills
                response = self.make_request("GET", "/bills")
                if response and response.status_code == 200:
                    bills = response.json()
                    self.log_test("Get All Bills", True, f"Retrieved {len(bills)} bills")
                else:
                    self.log_test("Get All Bills", False, f"Failed to get bills with status {response.status_code if response else 'No response'}")
                
                return True
            except:
                self.log_test("Create Bill", False, "Invalid JSON response")
                return False
        else:
            self.log_test("Create Bill", False, f"Failed with status {response.status_code if response else 'No response'}")
            return False
    
    def test_dashboard_statistics(self):
        """Test dashboard statistics API"""
        print("\n=== Testing Dashboard Statistics ===")
        
        if not self.token:
            self.log_test("Dashboard Stats", False, "No authentication token available")
            return False
        
        response = self.make_request("GET", "/dashboard/stats")
        if response and response.status_code == 200:
            try:
                stats = response.json()
                required_fields = ["total_products", "total_customers", "total_categories", "total_bills", "low_stock_products", "today_sales"]
                
                missing_fields = [field for field in required_fields if field not in stats]
                if missing_fields:
                    self.log_test("Dashboard Stats", False, f"Missing required fields: {missing_fields}")
                    return False
                
                # Validate data types
                numeric_fields = ["total_products", "total_customers", "total_categories", "total_bills", "low_stock_products"]
                for field in numeric_fields:
                    if not isinstance(stats[field], int):
                        self.log_test("Dashboard Stats", False, f"Field {field} should be integer, got {type(stats[field])}")
                        return False
                
                if not isinstance(stats["today_sales"], (int, float)):
                    self.log_test("Dashboard Stats", False, f"Field today_sales should be numeric, got {type(stats['today_sales'])}")
                    return False
                
                self.log_test("Dashboard Stats", True, f"Stats: Products={stats['total_products']}, Customers={stats['total_customers']}, Categories={stats['total_categories']}, Bills={stats['total_bills']}, Low Stock={stats['low_stock_products']}, Today's Sales=Rs.{stats['today_sales']}")
                return True
            except:
                self.log_test("Dashboard Stats", False, "Invalid JSON response")
                return False
        else:
            self.log_test("Dashboard Stats", False, f"Failed with status {response.status_code if response else 'No response'}")
            return False
    
    def test_error_handling(self):
        """Test error handling scenarios"""
        print("\n=== Testing Error Handling ===")
        
        # Test unauthorized access
        temp_token = self.token
        self.token = "invalid_token"
        response = self.make_request("GET", "/products")
        if response and response.status_code == 401:
            self.log_test("Unauthorized Access", True, "Correctly rejected invalid token")
        else:
            self.log_test("Unauthorized Access", False, f"Should reject invalid token, got status {response.status_code if response else 'No response'}")
        
        self.token = temp_token
        
        # Test invalid login
        invalid_login = {"username": "invalid", "password": "invalid"}
        response = self.make_request("POST", "/auth/login", invalid_login, use_auth=False)
        if response and response.status_code == 401:
            self.log_test("Invalid Login", True, "Correctly rejected invalid credentials")
        else:
            self.log_test("Invalid Login", False, f"Should reject invalid credentials, got status {response.status_code if response else 'No response'}")
        
        # Test creating product with invalid category
        invalid_product = {
            "name": "Test Product",
            "category_id": "invalid-category-id",
            "price": 100.0,
            "quantity": 10
        }
        response = self.make_request("POST", "/products", invalid_product)
        if response and response.status_code == 404:
            self.log_test("Invalid Category ID", True, "Correctly rejected invalid category ID")
        else:
            self.log_test("Invalid Category ID", False, f"Should reject invalid category ID, got status {response.status_code if response else 'No response'}")
    
    def run_all_tests(self):
        """Run all test suites"""
        print("🚀 Starting INFINITY Bookshop Backend API Tests")
        print(f"📍 Testing against: {self.base_url}")
        print("=" * 60)
        
        # Initialize sample data first
        self.test_sample_data_initialization()
        
        # Test authentication
        if not self.test_admin_authentication():
            print("❌ Authentication failed - stopping tests")
            return False
        
        # Test all CRUD operations
        self.test_category_management()
        self.test_product_management()
        self.test_customer_management()
        self.test_bill_generation()
        self.test_dashboard_statistics()
        self.test_error_handling()
        
        # Print summary
        self.print_summary()
        return True
    
    def print_summary(self):
        """Print test summary"""
        print("\n" + "=" * 60)
        print("📊 TEST SUMMARY")
        print("=" * 60)
        
        passed = sum(1 for result in self.test_results if result["success"])
        failed = len(self.test_results) - passed
        
        print(f"✅ Passed: {passed}")
        print(f"❌ Failed: {failed}")
        print(f"📈 Success Rate: {(passed/len(self.test_results)*100):.1f}%")
        
        if failed > 0:
            print("\n🔍 FAILED TESTS:")
            for result in self.test_results:
                if not result["success"]:
                    print(f"   ❌ {result['test']}: {result['message']}")
        
        print("\n" + "=" * 60)

def main():
    """Main test execution"""
    tester = BookshopAPITester()
    success = tester.run_all_tests()
    
    if not success:
        sys.exit(1)
    
    # Check if any critical tests failed
    critical_tests = ["Admin Login", "JWT Token Validation", "Create Bill", "Dashboard Stats"]
    failed_critical = [result for result in tester.test_results 
                      if not result["success"] and result["test"] in critical_tests]
    
    if failed_critical:
        print(f"\n⚠️  Critical tests failed: {len(failed_critical)}")
        sys.exit(1)
    
    print("\n🎉 All tests completed successfully!")

if __name__ == "__main__":
    main()