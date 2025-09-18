from fastapi import FastAPI, APIRouter, HTTPException, status, Depends
from fastapi.security import HTTPBearer, HTTPAuthorizationCredentials
from fastapi.responses import JSONResponse
from dotenv import load_dotenv
from starlette.middleware.cors import CORSMiddleware
from motor.motor_asyncio import AsyncIOMotorClient
import os
import logging
from pathlib import Path
from pydantic import BaseModel, Field
from typing import List, Optional, Dict, Any
import uuid
from datetime import datetime, timezone
import bcrypt
import jwt
from datetime import timedelta

ROOT_DIR = Path(__file__).parent
load_dotenv(ROOT_DIR / '.env')

# MongoDB connection
mongo_url = os.environ['MONGO_URL']
client = AsyncIOMotorClient(mongo_url)
db = client[os.environ['DB_NAME']]

# JWT Configuration
JWT_SECRET = os.environ.get('JWT_SECRET', 'your-secret-key-change-this')
JWT_ALGORITHM = "HS256"
JWT_EXPIRATION_HOURS = 24

# Create the main app without a prefix
app = FastAPI()

# Create a router with the /api prefix
api_router = APIRouter(prefix="/api")

# Security
security = HTTPBearer()

# Pydantic Models
class User(BaseModel):
    id: str = Field(default_factory=lambda: str(uuid.uuid4()))
    username: str
    password: str
    created_at: datetime = Field(default_factory=lambda: datetime.now(timezone.utc))

class UserLogin(BaseModel):
    username: str
    password: str

class UserResponse(BaseModel):
    id: str
    username: str
    created_at: datetime

class Category(BaseModel):
    id: str = Field(default_factory=lambda: str(uuid.uuid4()))
    name: str
    description: Optional[str] = ""
    created_at: datetime = Field(default_factory=lambda: datetime.now(timezone.utc))

class CategoryCreate(BaseModel):
    name: str
    description: Optional[str] = ""

class Product(BaseModel):
    id: str = Field(default_factory=lambda: str(uuid.uuid4()))
    name: str
    category_id: str
    category_name: Optional[str] = ""
    price: float
    quantity: int
    image_url: Optional[str] = ""
    description: Optional[str] = ""
    created_at: datetime = Field(default_factory=lambda: datetime.now(timezone.utc))

class ProductCreate(BaseModel):
    name: str
    category_id: str
    price: float
    quantity: int
    image_url: Optional[str] = ""
    description: Optional[str] = ""

class ProductUpdate(BaseModel):
    name: Optional[str] = None
    category_id: Optional[str] = None
    price: Optional[float] = None
    quantity: Optional[int] = None
    image_url: Optional[str] = None
    description: Optional[str] = None

class Customer(BaseModel):
    id: str = Field(default_factory=lambda: str(uuid.uuid4()))
    name: str
    contact: str
    email: Optional[str] = ""
    address: Optional[str] = ""
    created_at: datetime = Field(default_factory=lambda: datetime.now(timezone.utc))

class CustomerCreate(BaseModel):
    name: str
    contact: str
    email: Optional[str] = ""
    address: Optional[str] = ""

class BillItem(BaseModel):
    product_id: str
    product_name: str
    quantity: int
    price: float
    subtotal: float

class Bill(BaseModel):
    id: str = Field(default_factory=lambda: str(uuid.uuid4()))
    bill_number: str
    customer_id: str
    customer_name: str
    customer_contact: str
    date: datetime = Field(default_factory=lambda: datetime.now(timezone.utc))
    items: List[BillItem]
    total: float
    created_at: datetime = Field(default_factory=lambda: datetime.now(timezone.utc))

class BillCreate(BaseModel):
    customer_id: str
    items: List[Dict[str, Any]]  # {product_id, quantity}

class DashboardStats(BaseModel):
    total_products: int
    total_customers: int
    total_categories: int
    total_bills: int
    low_stock_products: int
    today_sales: float

# Helper Functions
def hash_password(password: str) -> str:
    return bcrypt.hashpw(password.encode('utf-8'), bcrypt.gensalt()).decode('utf-8')

def verify_password(password: str, hashed: str) -> bool:
    return bcrypt.checkpw(password.encode('utf-8'), hashed.encode('utf-8'))

def create_access_token(data: dict, expires_delta: timedelta = None):
    to_encode = data.copy()
    if expires_delta:
        expire = datetime.now(timezone.utc) + expires_delta
    else:
        expire = datetime.now(timezone.utc) + timedelta(hours=JWT_EXPIRATION_HOURS)
    to_encode.update({"exp": expire})
    encoded_jwt = jwt.encode(to_encode, JWT_SECRET, algorithm=JWT_ALGORITHM)
    return encoded_jwt

async def get_current_user(credentials: HTTPAuthorizationCredentials = Depends(security)):
    try:
        payload = jwt.decode(credentials.credentials, JWT_SECRET, algorithms=[JWT_ALGORITHM])
        username: str = payload.get("sub")
        if username is None:
            raise HTTPException(status_code=401, detail="Invalid authentication credentials")
        return username
    except jwt.PyJWTError:
        raise HTTPException(status_code=401, detail="Invalid authentication credentials")

def prepare_for_mongo(data):
    """Convert datetime objects to ISO strings for MongoDB storage"""
    if isinstance(data, dict):
        for key, value in data.items():
            if isinstance(value, datetime):
                data[key] = value.isoformat()
    return data

# Authentication Routes
@api_router.post("/auth/login")
async def login(user_data: UserLogin):
    user = await db.users.find_one({"username": user_data.username})
    if not user or not verify_password(user_data.password, user["password"]):
        raise HTTPException(status_code=401, detail="Invalid credentials")
    
    access_token = create_access_token(data={"sub": user["username"]})
    return {"access_token": access_token, "token_type": "bearer", "user": UserResponse(**user)}

@api_router.post("/auth/register", response_model=UserResponse)
async def register(user_data: UserLogin):
    # Check if user exists
    existing_user = await db.users.find_one({"username": user_data.username})
    if existing_user:
        raise HTTPException(status_code=400, detail="Username already exists")
    
    # Create new user
    user = User(username=user_data.username, password=hash_password(user_data.password))
    user_dict = prepare_for_mongo(user.dict())
    await db.users.insert_one(user_dict)
    return UserResponse(**user.dict())

# Dashboard Routes
@api_router.get("/dashboard/stats", response_model=DashboardStats)
async def get_dashboard_stats(current_user: str = Depends(get_current_user)):
    # Get counts
    total_products = await db.products.count_documents({})
    total_customers = await db.customers.count_documents({})
    total_categories = await db.categories.count_documents({})
    total_bills = await db.bills.count_documents({})
    
    # Low stock products (quantity < 10)
    low_stock_products = await db.products.count_documents({"quantity": {"$lt": 10}})
    
    # Today's sales
    today = datetime.now(timezone.utc).replace(hour=0, minute=0, second=0, microsecond=0)
    today_bills = await db.bills.find({"date": {"$gte": today.isoformat()}}).to_list(length=None)
    today_sales = sum(bill.get("total", 0) for bill in today_bills)
    
    return DashboardStats(
        total_products=total_products,
        total_customers=total_customers,
        total_categories=total_categories,
        total_bills=total_bills,
        low_stock_products=low_stock_products,
        today_sales=today_sales
    )

# Category Routes
@api_router.get("/categories", response_model=List[Category])
async def get_categories(current_user: str = Depends(get_current_user)):
    categories = await db.categories.find().to_list(length=None)
    return [Category(**category) for category in categories]

@api_router.post("/categories", response_model=Category)
async def create_category(category_data: CategoryCreate, current_user: str = Depends(get_current_user)):
    category = Category(**category_data.dict())
    category_dict = prepare_for_mongo(category.dict())
    await db.categories.insert_one(category_dict)
    return category

@api_router.put("/categories/{category_id}", response_model=Category)
async def update_category(category_id: str, category_data: CategoryCreate, current_user: str = Depends(get_current_user)):
    category = await db.categories.find_one({"id": category_id})
    if not category:
        raise HTTPException(status_code=404, detail="Category not found")
    
    update_data = category_data.dict()
    await db.categories.update_one({"id": category_id}, {"$set": update_data})
    
    updated_category = await db.categories.find_one({"id": category_id})
    return Category(**updated_category)

@api_router.delete("/categories/{category_id}")
async def delete_category(category_id: str, current_user: str = Depends(get_current_user)):
    # Check if category has products
    products = await db.products.count_documents({"category_id": category_id})
    if products > 0:
        raise HTTPException(status_code=400, detail="Cannot delete category with existing products")
    
    result = await db.categories.delete_one({"id": category_id})
    if result.deleted_count == 0:
        raise HTTPException(status_code=404, detail="Category not found")
    return {"message": "Category deleted successfully"}

# Product Routes
@api_router.get("/products", response_model=List[Product])
async def get_products(current_user: str = Depends(get_current_user)):
    products = await db.products.aggregate([
        {
            "$lookup": {
                "from": "categories",
                "localField": "category_id",
                "foreignField": "id",
                "as": "category"
            }
        },
        {
            "$addFields": {
                "category_name": {"$arrayElemAt": ["$category.name", 0]}
            }
        },
        {
            "$project": {"category": 0}
        }
    ]).to_list(length=None)
    return [Product(**product) for product in products]

@api_router.post("/products", response_model=Product)
async def create_product(product_data: ProductCreate, current_user: str = Depends(get_current_user)):
    # Verify category exists
    category = await db.categories.find_one({"id": product_data.category_id})
    if not category:
        raise HTTPException(status_code=404, detail="Category not found")
    
    product = Product(**product_data.dict(), category_name=category["name"])
    product_dict = prepare_for_mongo(product.dict())
    await db.products.insert_one(product_dict)
    return product

@api_router.put("/products/{product_id}", response_model=Product)
async def update_product(product_id: str, product_data: ProductUpdate, current_user: str = Depends(get_current_user)):
    product = await db.products.find_one({"id": product_id})
    if not product:
        raise HTTPException(status_code=404, detail="Product not found")
    
    update_data = {k: v for k, v in product_data.dict().items() if v is not None}
    
    # If category is being updated, get category name
    if "category_id" in update_data:
        category = await db.categories.find_one({"id": update_data["category_id"]})
        if not category:
            raise HTTPException(status_code=404, detail="Category not found")
        update_data["category_name"] = category["name"]
    
    await db.products.update_one({"id": product_id}, {"$set": update_data})
    
    updated_product = await db.products.find_one({"id": product_id})
    return Product(**updated_product)

@api_router.delete("/products/{product_id}")
async def delete_product(product_id: str, current_user: str = Depends(get_current_user)):
    result = await db.products.delete_one({"id": product_id})
    if result.deleted_count == 0:
        raise HTTPException(status_code=404, detail="Product not found")
    return {"message": "Product deleted successfully"}

# Customer Routes
@api_router.get("/customers", response_model=List[Customer])
async def get_customers(current_user: str = Depends(get_current_user)):
    customers = await db.customers.find().to_list(length=None)
    return [Customer(**customer) for customer in customers]

@api_router.post("/customers", response_model=Customer)
async def create_customer(customer_data: CustomerCreate, current_user: str = Depends(get_current_user)):
    customer = Customer(**customer_data.dict())
    customer_dict = prepare_for_mongo(customer.dict())
    await db.customers.insert_one(customer_dict)
    return customer

@api_router.put("/customers/{customer_id}", response_model=Customer)
async def update_customer(customer_id: str, customer_data: CustomerCreate, current_user: str = Depends(get_current_user)):
    customer = await db.customers.find_one({"id": customer_id})
    if not customer:
        raise HTTPException(status_code=404, detail="Customer not found")
    
    update_data = customer_data.dict()
    await db.customers.update_one({"id": customer_id}, {"$set": update_data})
    
    updated_customer = await db.customers.find_one({"id": customer_id})
    return Customer(**updated_customer)

@api_router.delete("/customers/{customer_id}")
async def delete_customer(customer_id: str, current_user: str = Depends(get_current_user)):
    result = await db.customers.delete_one({"id": customer_id})
    if result.deleted_count == 0:
        raise HTTPException(status_code=404, detail="Customer not found")
    return {"message": "Customer deleted successfully"}

# Bill Routes
@api_router.get("/bills", response_model=List[Bill])
async def get_bills(current_user: str = Depends(get_current_user)):
    bills = await db.bills.find().sort("date", -1).to_list(length=None)
    return [Bill(**bill) for bill in bills]

@api_router.post("/bills", response_model=Bill)
async def create_bill(bill_data: BillCreate, current_user: str = Depends(get_current_user)):
    # Get customer
    customer = await db.customers.find_one({"id": bill_data.customer_id})
    if not customer:
        raise HTTPException(status_code=404, detail="Customer not found")
    
    # Process items and calculate total
    bill_items = []
    total = 0
    
    for item_data in bill_data.items:
        product = await db.products.find_one({"id": item_data["product_id"]})
        if not product:
            raise HTTPException(status_code=404, detail=f"Product not found: {item_data['product_id']}")
        
        quantity = item_data["quantity"]
        if product["quantity"] < quantity:
            raise HTTPException(status_code=400, detail=f"Insufficient stock for product: {product['name']}")
        
        subtotal = product["price"] * quantity
        bill_items.append(BillItem(
            product_id=product["id"],
            product_name=product["name"],
            quantity=quantity,
            price=product["price"],
            subtotal=subtotal
        ))
        total += subtotal
        
        # Update product quantity
        await db.products.update_one(
            {"id": product["id"]},
            {"$inc": {"quantity": -quantity}}
        )
    
    # Generate bill number
    bill_count = await db.bills.count_documents({}) + 1
    bill_number = f"INF-{bill_count:05d}"
    
    bill = Bill(
        bill_number=bill_number,
        customer_id=customer["id"],
        customer_name=customer["name"],
        customer_contact=customer["contact"],
        items=bill_items,
        total=total
    )
    
    bill_dict = prepare_for_mongo(bill.dict())
    await db.bills.insert_one(bill_dict)
    return bill

@api_router.get("/bills/{bill_id}", response_model=Bill)
async def get_bill(bill_id: str, current_user: str = Depends(get_current_user)):
    bill = await db.bills.find_one({"id": bill_id})
    if not bill:
        raise HTTPException(status_code=404, detail="Bill not found")
    return Bill(**bill)

# Initialize sample data
@api_router.post("/init-data")
async def initialize_sample_data():
    """Initialize the system with sample data for INFINITY Bookshop"""
    
    # Check if data already exists
    existing_categories = await db.categories.count_documents({})
    if existing_categories > 0:
        return {"message": "Sample data already exists"}
    
    # Create admin user
    admin_user = User(username="admin", password=hash_password("admin123"))
    admin_dict = prepare_for_mongo(admin_user.dict())
    await db.users.insert_one(admin_dict)
    
    # Create categories
    categories_data = [
        {"name": "School Books", "description": "Academic textbooks and educational materials"},
        {"name": "Stationery", "description": "Pens, pencils, notebooks and office supplies"},
        {"name": "Educational Materials", "description": "Learning aids and educational resources"},
        {"name": "Art Supplies", "description": "Drawing and creative materials"}
    ]
    
    categories = []
    for cat_data in categories_data:
        category = Category(**cat_data)
        categories.append(category)
        cat_dict = prepare_for_mongo(category.dict())
        await db.categories.insert_one(cat_dict)
    
    # Sample product images
    product_images = [
        "https://images.unsplash.com/photo-1497633762265-9d179a990aa6?crop=entropy&cs=srgb&fm=jpg&ixid=M3w3NDQ2Mzl8MHwxfHNlYXJjaHwxfHxzY2hvb2wlMjBib29rc3xlbnwwfHx8fDE3NTgxOTc4Nzh8MA&ixlib=rb-4.1.0&q=85",
        "https://images.unsplash.com/photo-1565022536102-f7645c84354a?crop=entropy&cs=srgb&fm=jpg&ixid=M3w3NDQ2Mzl8MHwxfHNlYXJjaHwyfHxzY2hvb2wlMjBib29rc3xlbnwwfHx8fDE3NTgxOTc4Nzh8MA&ixlib=rb-4.1.0&q=85",
        "https://images.unsplash.com/photo-1631173716529-fd1696a807b0?crop=entropy&cs=srgb&fm=jpg&ixid=M3w3NDk1ODB8MHwxfHNlYXJjaHwxfHxzdGF0aW9uZXJ5fGVufDB8fHx8MTc1ODE5Nzg4M3ww&ixlib=rb-4.1.0&q=85",
        "https://images.unsplash.com/photo-1456735190827-d1262f71b8a3?crop=entropy&cs=srgb&fm=jpg&ixid=M3w3NDk1ODB8MHwxfHNlYXJjaHwyfHxzdGF0aW9uZXJ5fGVufDB8fHx8MTc1ODE5Nzg4M3ww&ixlib=rb-4.1.0&q=85"
    ]
    
    # Create products
    products_data = [
        {"name": "Mathematics Textbook Grade 10", "category_id": categories[0].id, "price": 850.0, "quantity": 50, "image_url": product_images[0], "description": "Comprehensive mathematics textbook for grade 10 students"},
        {"name": "English Grammar Workbook", "category_id": categories[0].id, "price": 650.0, "quantity": 75, "image_url": product_images[1], "description": "Interactive English grammar exercises and activities"},
        {"name": "Science Laboratory Manual", "category_id": categories[0].id, "price": 950.0, "quantity": 30, "image_url": product_images[0], "description": "Practical science experiments and laboratory procedures"},
        {"name": "History of Sri Lanka", "category_id": categories[0].id, "price": 750.0, "quantity": 40, "image_url": product_images[1], "description": "Comprehensive history book covering Sri Lankan heritage"},
        {"name": "Blue Ballpoint Pens (Pack of 10)", "category_id": categories[1].id, "price": 200.0, "quantity": 100, "image_url": product_images[2], "description": "High-quality ballpoint pens for everyday writing"},
        {"name": "HB Pencils (Pack of 12)", "category_id": categories[1].id, "price": 150.0, "quantity": 120, "image_url": product_images[3], "description": "Standard HB pencils perfect for writing and drawing"},
        {"name": "A4 Ruled Notebooks", "category_id": categories[1].id, "price": 300.0, "quantity": 80, "image_url": product_images[2], "description": "200-page ruled notebooks suitable for all subjects"},
        {"name": "Geometry Set", "category_id": categories[1].id, "price": 450.0, "quantity": 60, "image_url": product_images[3], "description": "Complete geometry set with compass, protractor, and rulers"},
        {"name": "Educational World Map", "category_id": categories[2].id, "price": 1200.0, "quantity": 25, "image_url": product_images[0], "description": "Large educational world map for classroom use"},
        {"name": "Calculator Scientific", "category_id": categories[2].id, "price": 2500.0, "quantity": 35, "image_url": product_images[1], "description": "Advanced scientific calculator for mathematics and science"},
        {"name": "Colored Pencils Set (24 colors)", "category_id": categories[3].id, "price": 800.0, "quantity": 45, "image_url": product_images[2], "description": "Professional colored pencils for art and drawing"},
        {"name": "Art Sketchbook A3", "category_id": categories[3].id, "price": 600.0, "quantity": 30, "image_url": product_images[3], "description": "High-quality drawing paper for sketching and artwork"}
    ]
    
    for prod_data in products_data:
        product = Product(**prod_data)
        prod_dict = prepare_for_mongo(product.dict())
        await db.products.insert_one(prod_dict)
    
    # Create sample customers
    customers_data = [
        {"name": "Amal Perera", "contact": "0771234567", "email": "amal@email.com", "address": "123 Main Street, Colombo 07"},
        {"name": "Nimal Silva", "contact": "0779876543", "email": "nimal@email.com", "address": "456 Galle Road, Dehiwala"},
        {"name": "Kamala Jayawardena", "contact": "0763456789", "email": "kamala@email.com", "address": "789 Kandy Road, Peradeniya"},
        {"name": "Sunil Fernando", "contact": "0785551234", "email": "sunil@email.com", "address": "321 High Level Road, Nugegoda"},
        {"name": "Priya Rajapaksa", "contact": "0771119999", "email": "priya@email.com", "address": "654 Temple Road, Mount Lavinia"}
    ]
    
    for cust_data in customers_data:
        customer = Customer(**cust_data)
        cust_dict = prepare_for_mongo(customer.dict())
        await db.customers.insert_one(cust_dict)
    
    return {"message": "Sample data initialized successfully", "admin_credentials": {"username": "admin", "password": "admin123"}}

# Include the router in the main app
app.include_router(api_router)

app.add_middleware(
    CORSMiddleware,
    allow_credentials=True,
    allow_origins=os.environ.get('CORS_ORIGINS', '*').split(','),
    allow_methods=["*"],
    allow_headers=["*"],
)

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)

@app.on_event("shutdown")
async def shutdown_db_client():
    client.close()