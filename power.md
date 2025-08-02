# 🚀 Plaza Festival API Documentation

**Version:** 2.0  
**Base URL:** `https://service.plazafestival-gmsb.co.id/api`  
**Updated:** August 2, 2025

---

## 📖 Table of Contents

1. [Authentication API](#authentication-api)
2. [Facility Management API](#facility-management-api)
3. [Transaction API](#transaction-api)
4. [Article API](#article-api)
5. [Welcome & Statistics API](#welcome--statistics-api)
6. [Restaurant Menu API](#restaurant-menu-api)
7. [Sport Venue API](#sport-venue-api)
8. [Booking Details API](#booking-details-api)
9. [Payment Gateway (Midtrans) API](#payment-gateway-midtrans-api)
10. [Error Handling](#error-handling)
11. [Rate Limiting](#rate-limiting)
12. [Testing Examples](#testing-examples)

---

## 🔐 Authentication API

### Base Route: `/api/auth`

#### 1. **Register User**
- **Endpoint:** `POST /api/auth/register`
- **Description:** Register a new user account
- **Authentication:** None required

**Request Headers:**
```http
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
    "name": "John Doe",
    "email": "john.doe@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "phone": "08123456789",
    "address": "Jl. Sudirman No. 123",
    "city": "Jakarta",
    "postal_code": "12190"
}
```

**Success Response (201):**
```json
{
    "success": true,
    "message": "User registered successfully",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john.doe@example.com"
        },
        "token": "1|abcdef123456789...",
        "token_type": "Bearer",
        "expires_at": "2025-09-01T10:30:00.000000Z"
    }
}
```

#### 2. **Login User**
- **Endpoint:** `POST /api/auth/login`
- **Description:** Authenticate user and get access token
- **Authentication:** None required

**Request Body:**
```json
{
    "email": "john.doe@example.com",
    "password": "password123",
    "remember_me": true
}
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "user": { ... },
        "token": "2|xyz789abc123def...",
        "token_type": "Bearer",
        "expires_at": "2025-09-01T11:00:00.000000Z"
    }
}
```

#### 3. **Protected Routes** (Require Authentication)
All protected routes require this header:
```http
Authorization: Bearer [your_token_here]
```

- `POST /api/auth/logout` - Logout and invalidate token
- `GET /api/auth/profile` - Get user profile
- `PUT /api/auth/profile` - Update user profile
- `POST /api/auth/change-password` - Change user password
- `POST /api/auth/refresh-token` - Refresh access token

---

## 🏢 Facility Management API

### 1. **Get All Facilities**
- **Endpoint:** `GET /api/getlistFasility`
- **Description:** Retrieve list of all available facilities
- **Authentication:** None required

**Example Request:**
```bash
curl -X GET "https://service.plazafestival-gmsb.co.id/api/getlistFasility" \
  -H "Accept: application/json"
```

### 2. **Get Facility by ID**
- **Endpoint:** `GET /api/getlistFasility/{id}`
- **Description:** Get detailed information about a specific facility
- **Parameters:**
  - `id` (integer): Facility ID

**Example Request:**
```bash
curl -X GET "https://service.plazafestival-gmsb.co.id/api/getlistFasility/1" \
  -H "Accept: application/json"
```

### 3. **Get Facility Sub-details**
- **Endpoint:** `GET /api/getlistFasility/{id}/{sid}`
- **Description:** Get sub-facility details
- **Parameters:**
  - `id` (integer): Facility ID
  - `sid` (integer): Sub-facility ID

### 4. **Get Article List (Legacy)**
- **Endpoint:** `GET /api/getArtikelList`
- **Description:** Get list of articles through facility controller
- **Note:** Consider using `/api/articles` instead

---

## 💳 Transaction API

### 1. **Create Transaction**
- **Endpoint:** `POST /api/makeTransaction`
- **Description:** Create a new booking transaction
- **Authentication:** Optional (depends on implementation)

**Basic Transaction (Cash Payment):**
```json
{
    "idsubfacility": 1,
    "time_start": ["09:00", "10:00"],
    "price": 100000,
    "transactionpoin": "cash",
    "date_start": "2025-07-10"
}
```

**Advanced Transaction (Midtrans Payment):**
```json
{
    "idsubfacility": 1,
    "time_start": ["09:00", "10:00"],
    "price": 100000,
    "transactionpoin": "midtrans",
    "date_start": "2025-07-10",
    "detail": {
        "payment_type": "e_wallet",
        "customer_details": {
            "first_name": "John",
            "last_name": "Doe",
            "email": "john@example.com",
            "phone": "08123456789",
            "address": "Jl. Sudirman No. 1",
            "city": "Jakarta",
            "postal_code": "12190"
        },
        "bank": "bca",
        "ewallet_provider": "gopay",
        "store": "indomaret"
    }
}
```

**Success Response:**
```json
{
    "success": true,
    "transaction_id": 123,
    "message": "Transaction created successfully",
    "payment_url": "https://app.sandbox.midtrans.com/snap/v2/vtweb/...",
    "snap_token": "66e4fa55-xxxx-xxxx-xxxx-c632bc0c8bac",
    "order_id": "PLAZA-123-1673456789"
}
```

### 2. **Get Transaction Details**
- **Endpoint:** `GET /api/getTransaction`
- **Description:** Get specific transaction details with QR code
- **Parameters:**
  - `id` (query): Transaction ID

**Example Request:**
```bash
curl -X GET "https://service.plazafestival-gmsb.co.id/api/getTransaction?id=123" \
  -H "Accept: application/json"
```

### 3. **Get User Transactions**
- **Endpoint:** `GET /api/getUserTransactions`
- **Description:** Get all successful transactions for a specific user
- **Parameters:**
  - `user_id` (query): User ID to filter transactions

**Example Request:**
```bash
curl -X GET "https://service.plazafestival-gmsb.co.id/api/getUserTransactions?user_id=123" \
  -H "Accept: application/json"
```

**Success Response:**
```json
{
    "success": true,
    "message": "User transactions retrieved successfully",
    "data": [
        {
            "t_id": 1,
            "PolinID": 123,
            "price": "100000",
            "status": "success",
            "barcode": "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data='...'",
            "created_at": "2025-07-30T10:30:00"
        }
    ],
    "total_transactions": 5,
    "total_amount": "500000",
    "user_id": 123
}
```

---

## 📰 Article API

### 1. **Get Article List**
- **Endpoint:** `GET /api/articles`
- **Description:** Get list of published articles

### 2. **Get Article Details**
- **Endpoint:** `GET /api/articles/{id}`
- **Description:** Get detailed article content
- **Parameters:**
  - `id` (integer): Article ID

---

## 🏠 Welcome & Statistics API

### **Get Welcome Data**
- **Endpoint:** `GET /api/welcome`
- **Description:** Get welcome page data and statistics
- **Usage:** Perfect for dashboard or homepage content

---

## 🍽️ Restaurant Menu API

### **Get Restaurant Menu**
- **Endpoint:** `GET /api/menuresto/{id}`
- **Description:** Get menu list for specific restaurant
- **Parameters:**
  - `id` (integer): Restaurant ID

**Example Request:**
```bash
curl -X GET "https://service.plazafestival-gmsb.co.id/api/menuresto/1" \
  -H "Accept: application/json"
```

---

## ⚽ Sport Venue API

### **Get Sport Venues**
- **Endpoint:** `GET /api/listvenue`
- **Description:** Get list of available sport venues
- **Usage:** For sports facility booking systems

---

## 📋 Booking Details API

### **Get Booking Details**
- **Endpoint:** `GET /api/detailbooking`
- **Description:** Get list of booking details
- **Usage:** For booking management and history

---

## 💰 Payment Gateway (Midtrans) API

### 1. **Get Payment Methods**
- **Endpoint:** `GET /api/payment-methods`
- **Description:** Get list of available payment methods

**Response:**
```json
{
    "credit_card": "Credit Card",
    "bank_transfer": "Bank Transfer",
    "e_wallet": "E-Wallet (GoPay, OVO, DANA)",
    "convenience_store": "Convenience Store"
}
```

### 2. **Create Payment**
- **Endpoint:** `POST /api/midtrans/create-payment`
- **Description:** Create Midtrans payment transaction

**Request Body:**
```json
{
    "transaction_id": 123,
    "payment_type": "e_wallet",
    "customer_details": {
        "first_name": "John",
        "last_name": "Doe",
        "email": "john@example.com",
        "phone": "08123456789"
    },
    "ewallet_provider": "gopay"
}
```

### 3. **Payment Webhooks**
These endpoints handle Midtrans callbacks:

- `GET/POST /api/midtrans/notification` - Payment status notifications
- `POST /api/midtrans/success` - Payment success callback
- `POST /api/midtrans/failed` - Payment failure callback
- `GET/POST /api/midtrans/finish` - User returns from payment
- `GET/POST /api/midtrans/unfinish` - User abandons payment
- `GET/POST /api/midtrans/error` - Payment error callback

### 4. **Payment Status Check**
- **Endpoint:** `GET /api/payment-status`
- **Description:** Check payment status by order ID
- **Parameters:**
  - `order_id` (query): Order ID to check

### 5. **Debug & Testing Endpoints**
- `ANY /api/midtrans/debug` - Debug callback data
- `ANY /api/midtrans/test-raw-body` - Test raw body parsing

---

## ⚠️ Error Handling

### Common HTTP Status Codes

| Code | Status | Description |
|------|--------|-------------|
| 200 | OK | Request successful |
| 201 | Created | Resource created successfully |
| 400 | Bad Request | Invalid request parameters |
| 401 | Unauthorized | Authentication required or failed |
| 403 | Forbidden | Access denied |
| 404 | Not Found | Resource not found |
| 422 | Unprocessable Entity | Validation errors |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Internal Server Error | Server error |

### Error Response Format

```json
{
    "success": false,
    "message": "Error description",
    "error": "Detailed error message",
    "errors": {
        "field_name": ["Validation error message"]
    }
}
```

---

## 🔄 Rate Limiting

- **Login attempts:** Limited to prevent brute force attacks
- **API calls:** 60 requests per minute per IP
- **Payment endpoints:** Special rate limiting for security

---

## 🧪 Testing Examples

### cURL Examples

**Register User:**
```bash
curl -X POST "https://service.plazafestival-gmsb.co.id/api/auth/register" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

**Create Transaction:**
```bash
curl -X POST "https://service.plazafestival-gmsb.co.id/api/makeTransaction" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "idsubfacility": 1,
    "time_start": ["09:00", "10:00"],
    "price": 100000,
    "transactionpoin": "cash",
    "date_start": "2025-08-10"
  }'
```

**Get User Transactions:**
```bash
curl -X GET "https://service.plazafestival-gmsb.co.id/api/getUserTransactions?user_id=123" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer your_token_here"
```

### JavaScript/Fetch Examples

```javascript
// Login Function
async function login(email, password) {
    const response = await fetch('https://service.plazafestival-gmsb.co.id/api/auth/login', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ email, password })
    });
    
    const data = await response.json();
    
    if (response.ok) {
        localStorage.setItem('auth_token', data.data.token);
        return { success: true, data: data.data };
    } else {
        return { success: false, error: data.message };
    }
}

// Create Transaction
async function createTransaction(transactionData) {
    const token = localStorage.getItem('auth_token');
    
    const response = await fetch('https://service.plazafestival-gmsb.co.id/api/makeTransaction', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify(transactionData)
    });
    
    return await response.json();
}

// Get User Transactions
async function getUserTransactions(userId) {
    const token = localStorage.getItem('auth_token');
    
    const response = await fetch(`https://service.plazafestival-gmsb.co.id/api/getUserTransactions?user_id=${userId}`, {
        headers: {
            'Accept': 'application/json',
            'Authorization': `Bearer ${token}`
        }
    });
    
    return await response.json();
}
```

### PHP/cURL Examples

```php
<?php

// Login Function
function login($email, $password) {
    $curl = curl_init();
    
    curl_setopt_array($curl, [
        CURLOPT_URL => 'https://service.plazafestival-gmsb.co.id/api/auth/login',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            'email' => $email,
            'password' => $password
        ]),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json'
        ]
    ]);
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    return json_decode($response, true);
}

// Create Transaction
function createTransaction($transactionData, $token = null) {
    $curl = curl_init();
    
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json'
    ];
    
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    
    curl_setopt_array($curl, [
        CURLOPT_URL => 'https://service.plazafestival-gmsb.co.id/api/makeTransaction',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($transactionData),
        CURLOPT_HTTPHEADER => $headers
    ]);
    
    $response = curl_exec($curl);
    curl_close($curl);
    
    return json_decode($response, true);
}

?>
```

---

## 📞 Support & Contact

- **Documentation Version:** 2.0
- **Last Updated:** August 2, 2025
- **API Base URL:** https://service.plazafestival-gmsb.co.id/api
- **Environment:** Production

### Quick Reference URLs

- **Auth Endpoints:** `/api/auth/*`
- **Facility Data:** `/api/getlistFasility*`
- **Transactions:** `/api/*Transaction*`
- **Articles:** `/api/articles/*`
- **Payments:** `/api/midtrans/*`
- **Statistics:** `/api/welcome`

---

## 🔒 Security Notes

1. **Always use HTTPS** for all API requests
2. **Store tokens securely** - use secure storage mechanisms
3. **Validate input** on both client and server side
4. **Handle token expiration** gracefully
5. **Rate limiting** is enforced - handle 429 responses
6. **Payment webhooks** use signature verification for security

---

*This documentation covers all available endpoints in the Plaza Festival API. For specific implementation details or troubleshooting, refer to the individual controller files or contact the development team.*
