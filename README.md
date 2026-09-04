# Investment Ledger API

A Laravel REST API for managing investment clients, accounts, cash movements, and instrument holdings.

## Features

* Create clients
* Each client has one account
* Each account has one currency
* Deposit cash
* Withdraw cash
* Buy instruments
* Sell instruments
* Calculate current cash balance
* Calculate current instrument holdings
* Validate business rules
* Prevent insufficient withdrawals
* Prevent purchases without sufficient cash
* Prevent selling more instruments than currently held
* Append-only movements
* Database transactions
* Seed example data
* Automated feature tests

## Technologies

* PHP 8.2+
* Laravel 12
* SQLite
* REST API
* JSON
* PHPUnit

---

# Local Setup

Follow these steps to run the project locally.

## 1. Clone the repository

```bash
git clone https://github.com/merimanasieva/investment-ledger.git
```

## 2. Enter the project directory

```bash
cd investment-ledger
```

## 3. Install dependencies

```bash
composer install
```

## 4. Create the environment file

On Windows:

```bash
copy .env.example .env
```

## 5. Generate the application key

```bash
php artisan key:generate
```

## 6. Create the SQLite database

Create an empty file:

```text
database/database.sqlite
```

Make sure the `.env` file is configured to use SQLite.

## 7. Run migrations and seed the database

```bash
php artisan migrate:fresh --seed
```

This creates the database tables and inserts example data.

## 8. Start the development server

```bash
php artisan serve
```

The API will be available at:

```text
http://127.0.0.1:8000
```

---

# Communication with the System

The system communicates through a REST API using HTTP requests and JSON.

The main HTTP methods used are:

* `GET` — retrieve information
* `POST` — create clients and movements

The following examples were tested using Thunder Client.

---

## 1. Create Client

### Request

```http
POST http://127.0.0.1:8000/api/clients
Content-Type: application/json
```

```json
{
    "name": "Marko",
    "currency": "USD"
}
```

### Response

In this test, the request was rejected because the client name already existed:

```json
{
    "message": "The name has already been taken.",
    "errors": {
        "name": [
            "The name has already been taken."
        ]
    }
}
```

This demonstrates the validation rule that client names must be unique.

---

## 2. Get Client

### Request

```http
GET http://127.0.0.1:8000/api/clients/1
```

### Response

```json
{
    "id": 1,
    "name": "Ana",
    "created_at": "2026-09-04T11:30:41.000000Z",
    "updated_at": "2026-09-04T11:30:41.000000Z",
    "account": {
        "id": 1,
        "client_id": 1,
        "currency": "EUR",
        "created_at": "2026-09-04T11:30:41.000000Z",
        "updated_at": "2026-09-04T11:30:41.000000Z"
    }
}
```

---

## 3. Get Client Balance

### Request

```http
GET http://127.0.0.1:8000/api/clients/1/balance
```

### Response

```json
{
    "client": "Ana",
    "currency": "EUR",
    "cash": "860.00",
    "holdings": {
        "AAPL": 2
    }
}
```

The response shows the client's current cash balance and instrument holdings.

---

## 4. Create Movement — Sell

### Request

```http
POST http://127.0.0.1:8000/api/clients/1/movements
Content-Type: application/json
```

```json
{
    "type": "sell",
    "instrument": "AAPL",
    "quantity": 1,
    "price": 120
}
```

### Response

```json
{
    "message": "Movement created successfully.",
    "movement": {
        "account_id": 1,
        "type": "sell",
        "amount": "120.00",
        "instrument": "AAPL",
        "quantity": 1,
        "price": "120.00",
        "updated_at": "2026-09-04T16:40:18.000000Z",
        "created_at": "2026-09-04T16:40:18.000000Z",
        "id": 6
    },
    "balance": {
        "cash": "980.00",
        "holdings": {
            "AAPL": 1
        }
    }
}
```

The sale increases the cash balance and decreases the number of held AAPL shares.

---

# Business Rules

## Cash Balance

A client cannot withdraw or spend more cash than is currently available.

For example, a purchase is rejected when the account does not have enough cash.

The account cannot have a negative cash balance.

## Holdings

A client cannot sell more units of an instrument than they currently own.

For example, if the client owns 2 AAPL shares, selling 8 AAPL shares is rejected.

## Positive Values

Amounts and prices must be greater than zero.

Quantities must be positive integers.

## Unique Client Names

Each client name must be unique.

If an existing name is used, the API returns a validation error.

## Append-only Movements

Movements are not updated or deleted through the API.

Each movement represents a transaction in the account history.

---

# Balance Calculation

The current cash balance is calculated from the movement history.

```text
Deposit     → increases cash
Withdrawal  → decreases cash
Buy         → decreases cash
Sell        → increases cash
```

Instrument holdings are also calculated from the movement history:

```text
Buy         → increases quantity
Sell        → decreases quantity
```

This means the current account state is based on the recorded movements instead of manually changing a separate balance value.

---

# Why This Approach?

I chose a movement-based ledger instead of storing a balance that is directly changed after every operation.

This makes every transaction traceable because the current balance can be calculated from the movement history. Movements are append-only, so previous transactions are not changed or deleted.

The main business rules are placed in a dedicated service so that deposits, withdrawals, buys, and sells follow the same validation logic while keeping the controllers simpler.

Database transactions are used when creating movements so that if an operation fails, the account is not left in a partially changed state.

Each movement belongs to a specific account, and each account belongs to one client. This keeps the relationships between clients, accounts, and movements clear.

---

# Testing

Run the automated tests with:

```bash
php artisan test
```

The tests cover important business rules and API behaviour, including:

* Client creation
* Deposits
* Withdrawals
* Insufficient cash
* Purchases
* Sales
* Selling more instruments than owned
* Invalid amounts
* Validation errors
* Account state after rejected movements

---

# Example Scenario

A client starts with:

```text
Deposit: 1000 EUR
```

Then buys:

```text
5 AAPL × 100 EUR = 500 EUR
```

Cash becomes:

```text
500 EUR
```

Then sells:

```text
3 AAPL × 120 EUR = 360 EUR
```

Final state:

```text
Cash: 860 EUR
AAPL: 2 units
```

This demonstrates how the account balance and holdings are derived from the recorded movements.
