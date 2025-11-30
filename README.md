Flash Sale API (Laravel 12 Enterprise Edition)

A high-concurrency, atomic inventory system designed to handle flash sales without overselling.

## **🏗 Architectural Decisions & Standards**

### **1\. Concurrency Strategy: Database Atomicity**

We strictly avoid "Read-Modify-Write" cycles in PHP. Instead, we rely on the ACID guarantees of the InnoDB engine.

* **Stock Reservation:** Uses a single atomic query:  
  UPDATE products SET available\_stock \= available\_stock \- ?   
  WHERE id \= ? AND available\_stock \>= ?

  This eliminates the need for complex distributed locks (Redlock) while guaranteeing correctness at the storage layer. If the query affects 0 rows, the request fails immediately.

### **2\. Design Pattern: Action-Domain-Responder (ADR)**

We move away from "Fat Controllers" to Stateless, Readonly Actions.

* **Actions:** final readonly classes encapsulating single business rules (e.g., CreateHoldAction).  
* **Controllers:** "Skinny" endpoints that delegate to Actions and return strictly typed JsonResources.  
* **FormRequests:** Validation is decoupled from the controller layer.

### **3\. Distributed Consistency (Webhooks)**

To handle **Out-of-Order Webhooks** (e.g., webhook arrives before the Order transaction commits):

* **Strategy:** **Fail Fast & Retry.**  
* If the webhook handler cannot find the Order ID, it throws a 404 Not Found.  
* This signals the Payment Provider (e.g., Stripe) to **retry** the delivery (Exponential Backoff).  
* This ensures strict consistency without needing complex "Orphaned Event" tables.

### **4\. Performance (Cache-Aside)**

* **Read Heavy:** GET /products/{id} is served via Redis (Cache::remember).  
* **Write Heavy:** Any stock change (Hold created or released) immediately invalidates the cache key to ensure data freshness.

## **🚀 Setup & Testing**

### **1\. Configuration (Crucial)**

The application **requires MySQL** (InnoDB) to handle row-level locking correctly. SQLite is not supported for the concurrency tests.

1. **Install Dependencies:**  
   composer install

2. **Environment Setup:**  
   cp .env.example .env

3. Database Configuration:  
   Open .env and ensure your database credentials are correct. You must create a database named flash\_sale (or update the config to match your local DB).  
   DB\_CONNECTION=mysql  
   DB\_HOST=127.0.0.1  
   DB\_PORT=3306  
   DB\_DATABASE=flash\_sale  \<-- Ensure this DB exists  
   DB\_USERNAME=root        \<-- Update if needed  
   DB\_PASSWORD=            \<-- Update if needed

4. **Initialize App:**  
   php artisan migrate:fresh \--seed

### **2\. Quick Start (For Reviewers)**

Run this single command to reset the database and get ready-to-use curl snippets for every endpoint:

php artisan demo:setup

### **3\. Concurrency Proof (The Stress Test)**

We run **30 parallel processes** against a stock of **10** to prove the system cannot oversell. This runs at the OS level to simulate true parallel traffic.

php artisan stress:inventory \--processes=30

**Expected Result:**

* ✅ Available Stock: 0  
* ✅ Total Holds: 10  
* ✅ Rejected: 20

### **4\. Automated Logic Tests**

Runs Unit and Feature tests for Idempotency, Expiry, and Order Logic.

php artisan test

### **5\. Manual Expiry Simulation**

To release expired holds and restore stock:

php artisan holds:release

## **📊 Metrics & Observability**

All logs use **Structured Context Arrays** for ingestion by Datadog/CloudWatch.

* **Concurrency Failures:** Logged as InventoryException (409 Conflict).  
* **Idempotency Hits:** Logged via ProcessWebhookAction when a duplicate webhook is detected.  
* **Stock Restoration:** The holds:release command logs exactly how many items were returned to the pool (stock\_restored).

## **🛠 API Endpoints**

| Method | Endpoint | Description |
| :---- | :---- | :---- |
| GET | /api/products/{id} | High-speed read (Cached). Returns ProductResource. |
| POST | /api/holds | Atomic stock reservation. Returns HoldResource. |
| POST | /api/orders | Converts Hold to Order. Returns OrderResource. |
| POST | /api/payments/webhook | Idempotent payment processing. Updates Order status. |

### **Tech Stack**

* **Framework:** Laravel 12.x  
* **Language:** PHP 8.4+ (Strict Types Enforced)  
* **Database:** MySQL 8.0+  
* **Cache:** Redis (Recommended) / Database (Fallback)
