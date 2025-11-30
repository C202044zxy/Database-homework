# SummitSphere Database System

A complete retail management database system with PHP web application for SummitSphere, a multi-branch cycling and adventure gear retailer.

## Overview

This project implements a full-stack solution including:
- **MySQL Database** with 15+ normalized tables
- **PHP Web Application** with role-based access control
- **CRUDS Operations** for all entities
- **Advanced SQL Features** (Window Functions, CTEs, Triggers, Stored Procedures)

## Project Structure

```
database/
├── sql/
│   ├── 01_create_database.sql    # Database schema (tables, indexes, constraints)
│   ├── 02_create_views.sql       # Role-based views for access control
│   ├── 03_create_procedures.sql  # Stored procedures and triggers
│   ├── 04_advanced_queries.sql   # Advanced SQL query examples
│   └── 05_seed_data.sql          # Sample data
│
├── php/
│   ├── config/
│   │   ├── config.php            # Application configuration
│   │   └── database.php          # Database connection class
│   ├── includes/
│   │   ├── auth.php              # Authentication handler
│   │   ├── header.php            # Common header template
│   │   └── footer.php            # Common footer template
│   ├── views/
│   │   ├── manager/              # Manager portal pages
│   │   ├── staff/                # Staff portal pages
│   │   ├── supplier/             # Supplier portal pages
│   │   └── customer/             # Customer portal pages
│   ├── assets/
│   │   ├── css/style.css         # Custom styles
│   │   └── js/app.js             # JavaScript functions
│   ├── index.php                 # Login page
│   ├── dashboard.php             # Role-based dashboard
│   └── logout.php                # Logout handler
│
└── docs/
    └── er_diagram.md             # Entity Relationship documentation
```

## Database Schema

### Core Entities (15 Tables)

| Table | Description |
|-------|-------------|
| `branch` | Physical store locations |
| `employee` | Staff with Manager/Staff roles |
| `supplier` | Product vendors |
| `customer` | End users with membership levels |
| `category` | Hierarchical product categories |
| `product` | Items for sale |
| `inventory` | Stock levels per branch per product |
| `customer_order` | Customer purchase orders |
| `order_item` | Products within orders |
| `payment` | Payment records |
| `shipment` | Supplier deliveries |
| `shipment_item` | Products within shipments |
| `review` | Customer product reviews |
| `user` | Authentication accounts |
| `audit_log` | Audit trail |
| `purchase_order` | Orders to suppliers |
| `purchase_order_item` | Products within purchase orders |

### Entity Relationship Diagram

```
                    ┌─────────────┐
                    │   BRANCH    │
                    └──────┬──────┘
                           │
              ┌────────────┼────────────┐
              │            │            │
       ┌──────▼──────┐ ┌───▼───┐ ┌──────▼──────┐
       │  EMPLOYEE   │ │INVENTORY│ │CUSTOMER_ORDER│
       └──────┬──────┘ └───┬───┘ └──────┬──────┘
              │            │            │
              │      ┌─────▼─────┐      │
              │      │  PRODUCT  │◄─────┤
              │      └─────┬─────┘      │
              │            │            │
              │     ┌──────┴──────┐     │
              │     │             │     │
         ┌────▼────┐│      ┌──────▼─────┐
         │  USER   ││      │ ORDER_ITEM │
         └─────────┘│      └────────────┘
                    │
              ┌─────▼─────┐     ┌──────────┐
              │ CATEGORY  │     │ SUPPLIER │
              └───────────┘     └────┬─────┘
                                     │
                              ┌──────▼──────┐
                              │  SHIPMENT   │
                              └─────────────┘
```

## User Roles & Access

| Role | Access Level |
|------|-------------|
| **Manager** | Full access - branches, employees, inventory, reports, analytics |
| **Staff** | Branch-specific - sales, orders, inventory at their location |
| **Supplier** | Own data - products, purchase orders, shipments |
| **Customer** | Personal - shop, cart, orders, reviews |

## Advanced SQL Features

### 1. Window Functions (04_advanced_queries.sql)
- Employee sales rankings with `RANK()` and `DENSE_RANK()`
- Month-over-month sales comparison with `LAG()`
- Running totals with `SUM() OVER()`
- Moving averages

### 2. Recursive CTEs
- Category hierarchy traversal
- Full path generation for nested categories
- Aggregate statistics for parent categories

### 3. Complex Subqueries
- Customer RFM (Recency, Frequency, Monetary) analysis
- Market basket analysis (frequently bought together)
- At-risk customer identification
- Supplier performance scorecards

### 4. Stored Procedures
- `sp_create_order` - Create new customer order
- `sp_add_order_item` - Add items with validation
- `sp_process_payment` - Process payment and update status
- `sp_update_membership_level` - Auto-update membership tiers
- `sp_get_low_stock_items` - Low stock alerts
- `sp_sales_report` - Generate sales reports

### 5. Triggers
- Auto-deduct inventory on order confirmation
- Restore inventory on order cancellation
- Auto-update membership after payment
- Audit logging for employee changes
- Verify purchase before review submission

## Installation

### Prerequisites
- MySQL 8.0+
- PHP 7.4+ with PDO extension
- Web server (Apache/Nginx)

### Database Setup

```bash
# Connect to MySQL
mysql -u root -p

# Run SQL files in order
source sql/01_create_database.sql
source sql/02_create_views.sql
source sql/03_create_procedures.sql
source sql/04_advanced_queries.sql
source sql/05_seed_data.sql
```

### PHP Configuration

1. Update database credentials in `php/config/database.php`:
```php
$this->host = 'localhost';
$this->db_name = 'summitsphere';
$this->username = 'your_username';
$this->password = 'your_password';
```

2. Configure web server to point to `php/` directory

### AWS Deployment

1. Launch EC2 instance with Amazon Linux/Ubuntu
2. Install LAMP stack (Apache, MySQL, PHP)
3. Transfer files to `/var/www/html/`
4. Configure MySQL with the SQL files
5. Update database credentials
6. Set appropriate file permissions

## Demo Accounts

| Role | Username | Password |
|------|----------|----------|
| Manager | thomas.anderson | password123 |
| Staff | emily.parker | password123 |
| Supplier | supplier.cyclegear | password123 |
| Customer | john.smith | password123 |

## Features by Role

### Manager Portal
- Dashboard with KPIs
- Branch management (CRUDS)
- Employee management (CRUDS)
- Supplier management (CRUDS)
- Customer overview
- Product catalog (CRUDS)
- Category management (CRUDS)
- Inventory management
- Order management
- Sales reports & analytics
- Low stock alerts

### Staff Portal
- Branch dashboard
- Product catalog (view)
- Inventory levels (view)
- Order management
- New sale creation

### Supplier Portal
- Dashboard
- Product catalog (own products)
- Purchase orders (view/update)
- Shipment tracking (update)

### Customer Portal
- Product browsing
- Shopping cart
- Order placement
- Order history
- Product reviews
- Membership status

## Policies Implemented

### Inventory Policy
- Stock tracked per branch
- Low-stock alerts when quantity <= min_stock_level
- Automatic deduction on order confirmation
- Automatic restoration on cancellation

### Membership Policy
- Bronze: 0% discount (default)
- Silver: 5% discount ($1,000+ spent)
- Gold: 10% discount ($5,000+ spent)
- Platinum: 15% discount ($10,000+ spent)
- Auto-upgraded via trigger on payment completion

### Data Access Policy
- Views restrict data per role
- Sensitive data (salaries, cooperation status) manager-only
- Customers see only their own orders/reviews
- Suppliers see only their own products/orders

## Security Features

- Password hashing with `password_hash()`
- CSRF token protection
- Prepared statements (PDO)
- Session timeout (30 minutes)
- Login attempt limiting
- Account locking after 5 failed attempts
- Input sanitization
- Role-based access control

## Technologies Used

- **Database**: MySQL 8.0
- **Backend**: PHP 7.4+
- **Frontend**: Bootstrap 5.3, Bootstrap Icons
- **Security**: PDO, bcrypt, CSRF tokens

## License

This project is for educational purposes as part of a database design assignment.

## Author

SummitSphere Database System - Assessment 2
