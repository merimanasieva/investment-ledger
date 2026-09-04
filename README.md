# Investment Ledger API

A Laravel REST API for managing investment client accounts, cash movements, and instrument holdings.

## Features

* Create clients
* Each client has exactly one account
* Each account has one currency
* Deposit cash
* Withdraw cash
* Buy instruments
* Sell instruments
* Calculate current cash balance
* Calculate current holdings
* Validate business rules
* Prevent insufficient withdrawals
* Prevent purchases without sufficient cash
* Prevent selling more instruments than currently held
* Append-only movements
* Seed example data
* Automated feature tests

## Technologies

* PHP 8.2+
* Laravel
* SQLite
* REST API
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

## 3. Install PHP dependencies

```bash
composer install
```

## 4. Create the environment file

Windows:

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

## 7. Run migrations and seed example data

```bash
php artisan migrate:fresh --seed
```

This creates the database structure and inserts example data.

## 8. Start the Laravel development server

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

The main communication methods are:

* `POST` — create a client or create a movement
* `GET` — retrieve client information, balance, or movements

## 1. Create Client

### Request

```http
POST /api/clients
Content-Type: application/json
```

```json
{
    "name": "Ana",
    "currency": "EUR"
}
```

### Response

```json
{
    "id": 1,
    "name": "Ana",
    "currency": "EUR"
}
```

---

## 2. Get Client

### Request

```http
GET /api/clients/1
```

### Response

```json
{
    "id": 1,
    "name": "Ana",
    "currency": "EUR"
}
```

---

## 3. Get Client Balance

### Request

```http
GET /api/clients/1/balance
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

The balance is calculated from the client's movement history.

---

## 4. Get Movements

### Request

```http
GET /api/clients/1/movements
```

### Response

```json
[
    {
        "type": "deposit",
        "amount": "1000.00"
    },
    {
        "type": "buy",
        "instrument": "AAPL",
        "quantity": 5,
        "price": "100.00"
    },
    {
        "type": "sell",
        "instrument": "AAPL",
        "quantity": 3,
        "price": "120.00"
    }
]
```

---

# Creating Movements

Movements are created using:

```http
POST /api/clients/{client}/movements
```

The `type` field determines what operation is performed.

## Deposit

### Request

```json
{
    "type": "deposit",
    "amount": 1000
}
```

### Response

```json
{
    "message": "Movement created successfully."
}
```

A deposit increases the client's cash balance.

---

## Withdrawal

### Request

```json
{
    "type": "withdrawal",
    "amount": 300
}
```

### Response

```json
{
    "message": "Movement created successfully."
}
```

A withdrawal decreases the client's cash balance.

A withdrawal is rejected if there is not enough available cash.

---

## Buy

### Request

```json
{
    "type": "buy",
    "instrument": "AAPL",
    "quantity": 5,
    "price": 100
}
```

The total purchase amount is calculated automatically:

```text
5 × 100 = 500 EUR
```

### Response

```json
{
    "message": "Movement created successfully."
}
```

The purchase decreases cash and increases the number of held instruments.

---

## Sell

### Request

```json
{
    "type": "sell",
    "instrument": "AAPL",
    "quantity": 3,
    "price": 120
}
```

The sale proceeds are calculated automatically:

```text
3 × 120 = 360 EUR
```

### Response

```json
{
    "message": "Movement created successfully."
}
```

The sale increases cash and decreases the number of held instruments.

A sale is rejected if the client does not own enough units of the instrument.

---

# Business Rules

## Cash

A client cannot withdraw or spend more cash than the current account balance.

The account can never have a negative cash balance.

## Holdings

A client cannot sell more units of an instrument than currently held.

## Positive Values

Amounts and prices must be greater than zero.

Quantities must be positive integers.

## Append-only Movements

Movements cannot be updated or deleted through the API.

They represent an immutable transaction history.

---

# Balance Calculation

The current cash balance is calculated from the movement history:

```text
Deposits      → increase cash
Withdrawals   → decrease cash
Buys          → decrease cash
Sells         → increase cash
```

Holdings are calculated from buy and sell movements:

```text
Buy           → increase quantity
Sell          → decrease quantity
```

No separate mutable balance table is required.

This keeps the current account state consistent with the movement history.

---

# Why This Approach?

I chose a movement-based ledger instead of storing a balance that is directly changed after every operation.

This makes every transaction traceable because the current balance can always be calculated from the movement history. Movements are append-only, so previous transactions are not changed or deleted.

I also placed the main business rules in a dedicated service. This keeps the controllers simpler and makes sure that deposits, withdrawals, buys, and sells follow the same validation rules.

Database transactions are used when creating movements so that if an operation fails, the account state is not partially changed.

Each movement belongs to a specific account, and each account belongs to one client. This keeps client data separated and makes the relationships between clients, accounts, and movements clear.

---

# Testing

Run all automated tests with:

```bash
php artisan test
```

The tests cover:

* Client creation
* Deposits
* Withdrawals
* Insufficient withdrawals
* Purchases
* Insufficient cash for purchases
* Sales
* Excessive sales
* Invalid amounts
* Account state remaining unchanged after rejected movements

---

# Example Scenario

Ana starts with:

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
