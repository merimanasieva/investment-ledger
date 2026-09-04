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

* PHP
* Laravel
* SQLite
* REST API
* PHPUnit

## Requirements

* PHP 8.2+
* Composer
* Laravel
* SQLite

## Installation

Clone the repository:

```bash
git clone YOUR_REPOSITORY_URL
```

Enter the project directory:

```bash
cd investment-ledger
```

Install dependencies:

```bash
composer install
```

Create the environment file:

```bash
copy .env.example .env
```

Generate the application key:

```bash
php artisan key:generate
```

Create the SQLite database if it does not exist:

```text
database/database.sqlite
```

Run migrations and seed example data:

```bash
php artisan migrate:fresh --seed
```

Start the development server:

```bash
php artisan serve
```

The API will be available at:

```text
http://127.0.0.1:8000
```

## API Endpoints

### Create Client

```http
POST /api/clients
```

Example:

```json
{
    "name": "Ana",
    "currency": "EUR"
}
```

### Get Client

```http
GET /api/clients/{client}
```

### Get Balance

```http
GET /api/clients/{client}/balance
```

Example response:

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

### Get Movements

```http
GET /api/clients/{client}/movements
```

### Create Movement

```http
POST /api/clients/{client}/movements
```

### Deposit

```json
{
    "type": "deposit",
    "amount": 1000
}
```

### Withdrawal

```json
{
    "type": "withdrawal",
    "amount": 300
}
```

### Buy

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
5 × 100 = 500
```

### Sell

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
3 × 120 = 360
```

## Business Rules

### Cash

A client cannot withdraw or spend more cash than the current account balance.

The account can never have a negative cash balance.

### Holdings

A client cannot sell more units of an instrument than currently held.

### Positive Values

Amounts and prices must be greater than zero.

Quantities must be positive integers.

### Append-only Movements

Movements cannot be updated or deleted through the API.

They represent an immutable transaction history.

## Balance Calculation

The current cash balance is calculated from the movement history:

```text
Deposits      + cash
Withdrawals   - cash
Buys          - cash
Sells         + cash
```

Holdings are calculated from buy and sell movements:

```text
Buy           + quantity
Sell          - quantity
```

No separate mutable balance table is required.

This keeps the ledger consistent with the movement history.

## Why This Approach?

The application uses a movement-based ledger instead of storing a mutable balance.

This makes every transaction traceable and keeps the movement history append-only.

Business rules are implemented in a dedicated service so that validation and account state changes are handled consistently.

Database transactions are used when creating movements. If a movement is invalid, the operation fails without changing the account state.

Client accounts are isolated because every movement belongs to a specific account, and every account belongs to exactly one client.

## Testing

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

## Example Scenario

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
