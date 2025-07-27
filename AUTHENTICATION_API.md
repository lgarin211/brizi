# PlazaFest Authentication API Documentation

## Authentication Endpoints

Base URL: `/api/auth`

### 1. Register User
**POST** `/api/auth/register`

Register a new user account.

#### Request Body:
```json
{
    "first_name": "John",
    "last_name": "Doe", 
    "email": "john.doe@example.com",
    "phone": "081234567890",
    "password": "password123",
    "password_confirmation": "password123",
    "address": "Jl. Sudirman No. 1", // optional
    "city": "Jakarta", // optional, default: "Jakarta"
    "postal_code": "12345", // optional, default: "12345"
    "date_of_birth": "1990-01-01" // optional, format: YYYY-MM-DD
}
```

#### Success Response (201):
```json
{
    "success": true,
    "message": "User registered successfully",
    "data": {
        "user": {
            "id": 1,
            "first_name": "John",
            "last_name": "Doe",
            "email": "john.doe@example.com",
            "phone": "081234567890",
            "address": "Jl. Sudirman No. 1",
            "city": "Jakarta",
            "postal_code": "12345",
            "date_of_birth": "1990-01-01",
            "is_active": true,
            "created_at": "2025-07-27T10:00:00.000000Z"
        },
        "api_token": "abc123...xyz",
        "token_type": "Bearer"
    }
}
```

#### Error Response (422):
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "email": ["The email has already been taken."],
        "password": ["The password confirmation does not match."]
    }
}
```

### 2. Login User
**POST** `/api/auth/login`

Login with email and password.

#### Request Body:
```json
{
    "email": "john.doe@example.com",
    "password": "password123"
}
```

#### Success Response (200):
```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "user": {
            "id": 1,
            "first_name": "John",
            "last_name": "Doe",
            "email": "john.doe@example.com",
            "phone": "081234567890",
            "address": "Jl. Sudirman No. 1",
            "city": "Jakarta",
            "postal_code": "12345",
            "date_of_birth": "1990-01-01",
            "is_active": true,
            "last_login_at": "2025-07-27T10:00:00.000000Z",
            "created_at": "2025-07-27T09:00:00.000000Z"
        },
        "api_token": "abc123...xyz",
        "token_type": "Bearer"
    }
}
```

#### Error Response (401):
```json
{
    "success": false,
    "message": "Invalid credentials"
}
```

### 3. Get User Profile
**GET** `/api/auth/profile`

Get current user profile information.

#### Headers:
```
Authorization: Bearer {api_token}
```

#### Success Response (200):
```json
{
    "success": true,
    "message": "Profile retrieved successfully",
    "data": {
        "user": {
            "id": 1,
            "first_name": "John",
            "last_name": "Doe",
            "email": "john.doe@example.com",
            "phone": "081234567890",
            "address": "Jl. Sudirman No. 1",
            "city": "Jakarta",
            "postal_code": "12345",
            "date_of_birth": "1990-01-01",
            "is_active": true,
            "email_verified_at": null,
            "last_login_at": "2025-07-27T10:00:00.000000Z",
            "created_at": "2025-07-27T09:00:00.000000Z",
            "updated_at": "2025-07-27T10:00:00.000000Z"
        }
    }
}
```

### 4. Update User Profile
**PUT** `/api/auth/profile`

Update user profile information.

#### Headers:
```
Authorization: Bearer {api_token}
```

#### Request Body:
```json
{
    "first_name": "Jane", // optional
    "last_name": "Smith", // optional
    "phone": "081987654321", // optional
    "address": "Jl. Thamrin No. 2", // optional
    "city": "Bandung", // optional
    "postal_code": "40123", // optional
    "date_of_birth": "1992-05-15" // optional
}
```

#### Success Response (200):
```json
{
    "success": true,
    "message": "Profile updated successfully",
    "data": {
        "user": {
            "id": 1,
            "first_name": "Jane",
            "last_name": "Smith",
            "email": "john.doe@example.com",
            "phone": "081987654321",
            "address": "Jl. Thamrin No. 2",
            "city": "Bandung",
            "postal_code": "40123",
            "date_of_birth": "1992-05-15",
            "is_active": true,
            "updated_at": "2025-07-27T11:00:00.000000Z"
        }
    }
}
```

### 5. Change Password
**POST** `/api/auth/change-password`

Change user password.

#### Headers:
```
Authorization: Bearer {api_token}
```

#### Request Body:
```json
{
    "current_password": "oldpassword123",
    "new_password": "newpassword123",
    "new_password_confirmation": "newpassword123"
}
```

#### Success Response (200):
```json
{
    "success": true,
    "message": "Password changed successfully"
}
```

#### Error Response (400):
```json
{
    "success": false,
    "message": "Current password is incorrect"
}
```

### 6. Refresh Token
**POST** `/api/auth/refresh-token`

Generate a new API token.

#### Headers:
```
Authorization: Bearer {api_token}
```

#### Success Response (200):
```json
{
    "success": true,
    "message": "Token refreshed successfully",
    "data": {
        "api_token": "new_abc123...xyz",
        "token_type": "Bearer"
    }
}
```

### 7. Logout
**POST** `/api/auth/logout`

Logout and invalidate current token.

#### Headers:
```
Authorization: Bearer {api_token}
```

#### Success Response (200):
```json
{
    "success": true,
    "message": "Logged out successfully"
}
```

## Error Responses

### 401 Unauthorized
```json
{
    "success": false,
    "message": "Authorization token required",
    "error": "No Bearer token provided"
}
```

### 401 Invalid Token
```json
{
    "success": false,
    "message": "Invalid or expired token",
    "error": "Authentication failed"
}
```

### 403 Inactive Account
```json
{
    "success": false,
    "message": "Account is inactive"
}
```

### 404 User Not Found
```json
{
    "success": false,
    "message": "Email not found"
}
```

### 409 Conflict
```json
{
    "success": false,
    "message": "Email already registered"
}
```

### 422 Validation Error
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "field_name": ["error message"]
    }
}
```

### 500 Server Error
```json
{
    "success": false,
    "message": "Failed to register user",
    "error": "Database connection failed"
}
```

## Usage Examples

### Using cURL

#### Register:
```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "first_name": "John",
    "last_name": "Doe",
    "email": "john.doe@example.com",
    "phone": "081234567890",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

#### Login:
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john.doe@example.com",
    "password": "password123"
  }'
```

#### Get Profile:
```bash
curl -X GET http://localhost:8000/api/auth/profile \
  -H "Authorization: Bearer your_api_token_here"
```

#### Logout:
```bash
curl -X POST http://localhost:8000/api/auth/logout \
  -H "Authorization: Bearer your_api_token_here"
```

## Integration with Existing PlazaFest Endpoints

Untuk menggunakan authentication dengan endpoint PlazaFest yang sudah ada, tinggal tambahkan middleware `api.token.auth` pada route yang ingin diproteksi:

```php
Route::middleware('api.token.auth')->group(function () {
    Route::post('/makeTransaction', [TransaksiController::class, 'makeTransaction']);
    Route::get('/getTransactionHistory', [TransaksiController::class, 'getTransactionHistory']);
    // ... endpoint lainnya yang perlu authentication
});
```
