# KK Wholesalers - Inventory Movement System

A high-integrity inventory management system built with **Laravel 12** and **Vue 3**. Designed to solve stock inconsistencies, race conditions, and audit challenges in a distributed retail environment.

---

##  Design Thinking & Architecture

### 1. Data Integrity & Concurrency
To address the "race conditions during peak sales" requirement, the system implements **Row-Level Locking**.
- **Implementation:** The `InventoryService` uses `lockForUpdate()` when querying a product's balance within a database transaction.
- **Benefit:** This prevents two simultaneous sales from "reading" the same balance before one has "written" the update, ensuring ghost stock is impossible.

### 2. Auditability (The Ledger Pattern)
Instead of just updating a `balance` column, the system maintains a `stock_movements` ledger.
- **Traceability:** Every change (Sale, Transfer, Adjustment, Procurement) creates a permanent record containing the `user_id`, `type`, `quantity`, and the resulting `balance` snapshot.
- **Verification:** The current balance in the `inventories` table can always be reconstructed by summing the ledger, providing a complete audit trail.

### 3. Hierarchical Authorization (RBAC)
The solution models the business structure (Branches -> Stores) and restricts access using **Laravel Policies**.
- **Admin:** Global access to all branches and stores.
- **Branch Manager:** Restricted to stores within their assigned branch.
- **Store Manager:** Restricted to their specific assigned store.
- **Extensibility:** Built using PHP Enums and Policies, making it easy to add granular permissions in the future.

### 4. Scalability
- **Database:** Optimized with indexes on `sku` and foreign keys.
- **Architecture:** Uses the **Service Pattern** to decouple business logic from Controllers, making the system ready for API-first consumption or background job processing.

---

## Features Implemented
- **Inventory Management:** Centralized product tracking across multiple stores.
- **Sales & Adjustments:** Atomic stock reductions and manual corrections.
- **Inter-Store Transfers:** Secure movement of stock between locations with dual-entry audit logs.
- **Procurement:** System seeding and stock intake.
- **Real-time Dashboard:** Vue 3 interface for viewing balances and audit history.

---

## Tech Stack
- **Backend:** PHP 8.4, Laravel 12
- **Frontend:** Vue 3, Tailwind CSS, Vite
- **Database:** MySQL
- **Testing:** Pest 4 (Feature & Unit testing)

---

## Getting Started

### Prerequisites
- PHP 8.4+
- Composer
- Node.js & NPM
- MySQL/MariaDB

### Installation
1. **Clone the repository:**
   ```bash
   git clone https://github.com/paullaster/inventory-service
   cd inventory-service
   ```

2. **Install Dependencies:**
   ```bash
   composer install
   npm install
   ```

3. **Environment Setup:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   *Note: Update `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` in `.env`.*

### 4. Database & Seeding
The system provides two ways to populate data:
- **Demo Mode (Recommended):** Creates a structured business hierarchy (2 Branches, 3 Stores, 10 Products, and 6 specialized Users).
  ```bash
  php artisan migrate --seed
  ```
- **Factory Mode:** Generates large-scale random datasets using model factories for stress testing.
  ```bash
  php artisan db:seed --class=ProductSeeder
  php artisan db:seed --class=StockMovementSeeder
  ```


5. **Build Assets:**
   ```bash
   npm run build
   ```

6. **Run the Application:**
   ```bash
   php artisan serve
   ```
   Visit `http://localhost:8000`

---

##  Testing
The system includes comprehensive tests for both service logic and API authorization.
```bash
php artisan test
```

##  Demo Credentials
| Role | Email | Password |
| :--- | :--- | :--- |
| **Admin** | `admin@kkwholesalers.com` | `password` |
| **Branch Manager** | `branchA@kkwholesalers.com` | `password` |
| **Store Manager** | `storeA1@kkwholesalers.com` | `password` |

---
