# MJ Casting - Gold Workshop Management System

## 📋 Requirements
- **Web Server**: Apache with mod_rewrite (or Nginx)
- **PHP**: 8.0 or higher
- **MySQL**: 5.7+ or MariaDB 10.3+
- **phpMyAdmin**: Optional - for database management

## 🚀 Quick Installation

### Step 1: Database Setup via phpMyAdmin

1. Open phpMyAdmin (usually at `http://localhost/phpmyadmin`)
2. Click on the **SQL** tab
3. Copy the entire contents of `db.sql` file
4. Paste and click **Go** to execute
5. This creates:
   - Database: `mj_casting`
   - All 8 tables (users, customers, invoices, invoice_receives, gold_receipts, gold_receipt_items, inventory, settings)
   - Default data (admin user, settings, inventory record)

### Step 2: Configure Database Connection

1. Open `config.php`
2. Update these values if different:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'mj_casting');
   define('DB_USER', 'root');    // Your MySQL username
   define('DB_PASS', '');        // Your MySQL password
   ```

### Step 3: Deploy Files

1. Copy the entire `mj_casting` folder to your web server
   - For XAMPP: `C:\xampp\htdocs\mj_casting\`
   - For WAMP: `C:\wamp64\www\mj_casting\`
   - For Linux: `/var/www/html/mj_casting/`

### Step 4: Access the Application

1. Open browser: `http://localhost/mj_casting/`
2. Login with:
   - **Admin**: `admin@goldworkshop.test` / `admin123`
   - **User**: `user@goldworkshop.test` / `user12345`

## 📁 File Structure

```
mj_casting/
├── config.php                 # Database & app configuration
├── db.sql                    # Complete database schema + data
├── index.php                 # Entry point (redirects to login/dashboard)
├── login.php                 # Login page
├── register.php             # Registration page
├── logout.php               # Logout
├── dashboard.php            # Main dashboard with stats
├── sync-status.php          # System status page
│
├── api/
│   └── customer_balance.php # AJAX endpoint for customer balance
│
├── assets/css/
│   └── style.css            # Complete dark theme CSS
│
├── customers/               # Party management (CRUD)
│   ├── index.php            # List with search/filter/pagination
│   ├── create.php           # Create party form
│   ├── edit.php             # Edit party form
│   └── show.php             # Party details + ledger
│
├── invoices/                # Invoice management (CRUD)
│   ├── index.php            # List with search/filter
│   ├── create.php           # Create invoice (with live calculation)
│   ├── edit.php             # Edit invoice
│   ├── show.php             # Invoice details
│   ├── print.php            # Print invoice (A5/Slip format)
│   └── delete.php           # Cancel invoice
│
├── gold-receipts/           # Gold receipt management (CRUD)
│   ├── index.php            # List with filters
│   ├── create.php           # Create receipt
│   ├── edit.php             # Edit receipt
│   ├── show.php             # Receipt details
│   ├── print.php            # Print receipt
│   └── delete.php           # Delete receipt
│
├── ledger/
│   └── index.php            # Customer ledger with transactions
│
├── inventory/
│   └── index.php            # Stock calculation & tracking
│
├── reports/
│   ├── daily.php            # Daily report (single date + range)
│   └── customer.php         # Customer report (date range)
│
├── settings/
│   └── index.php            # Workshop settings
│
├── exports/
│   └── invoices_csv.php     # CSV export
│
├── functions/
│   ├── gold_calculations.php # All gold calculation logic
│   └── ledger_functions.php  # Ledger & report logic
│
├── includes/
│   ├── header.php           # Layout header + sidebar
│   └── footer.php           # Layout footer
│
└── .htaccess                # URL rewriting
```

## 🔢 Gold Calculation Formulas

The system uses these exact gold calculation formulas:

| Step | Field | Formula |
|------|-------|---------|
| 1 | Waste Weight | Casting Weight ÷ 10 × Ratti Rate |
| 2 | Total Weight | Casting Weight + Waste Weight |
| 3 | Male Waste | Total Weight ÷ 96 × Ratti |
| 4 | Gold Khalis | Total Weight - Male Waste |
| 5 | RP Amount | Gold Khalis × RP Rate |
| 6 | RP Mazdori Amount | RP Mazdori Weight × RP Mazdori Rate |
| 7 | Casting Mazdori Amount | Casting Mazdori Weight × Casting Mazdori Rate |
| 8 | Effective Gold | Gold Khalis + RP Mazdori Weight + Casting Mazdori Weight |
| 9 | Grand Total | Effective Gold |
| 10 | Remaining Balance | Previous Balance + Effective Gold - Wasooli - Total Received Khalis |
| - | Khalis Conversion | Gross Weight - (Gross Weight ÷ 96 × Ratti Impurity) |
| - | Inventory Closing | Opening + Receipts + Invoice Receives + Internal Received - Invoice Given |

## 🧮 Balance Formula
```
Balance = Opening Balance + Effective Gold (Given) - Received (Khalis) - Wasooli
```

## 📊 Features

- ✅ **Multiple Party Types**: Customer, Dukandar, Karigar
- ✅ **Live Gold Calculation**: Real-time client-side calculation
- ✅ **Invoice Management**: Create, edit, view, print, cancel invoices
- ✅ **Gold Receipts**: Standalone gold receipt with item rows
- ✅ **Invoice Receives**: Gold received within invoice (dynamic rows)
- ✅ **Customer Ledger**: Full chronological transaction view
- ✅ **Inventory Tracking**: Opening + In - Out = Closing
- ✅ **Daily Reports**: Single date or date range
- ✅ **Customer Reports**: Per party with date range
- ✅ **CSV Export**: Export invoices to CSV
- ✅ **Print Formats**: A5 and thermal slip (80mm)
- ✅ **Dark Theme**: Premium UI with Urdu support
- ✅ **Pagination**: All list views with search/filter