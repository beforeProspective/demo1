# JWT Authentication API Documentation

## Base URL
```
http://localhost:8000/api
```

---

## Authentication Endpoints

### 1. User Registration
**POST** `/auth/register`

Register a new user account.

**Request Body:**
```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "role": "user"  // optional: "user" or "admin"
}
```

**Success Response (201 Created):**
```json
{
    "success": true,
    "message": "User registered successfully",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "role": "user"
        },
        "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
        "token_type": "bearer",
        "expires_in": 3600
    }
}
```

**Error Response (422 Validation Error):**
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "email": ["The email has already been taken."]
    }
}
```

---

### 2. User Login
**POST** `/auth/login`

Authenticate user and receive JWT token.

**Request Body:**
```json
{
    "email": "john@example.com",
    "password": "password123"
}
```

**Success Response (200 OK):**
```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "role": "user"
        },
        "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
        "token_type": "bearer",
        "expires_in": 3600
    }
}
```

**Error Response (401 Unauthorized):**
```json
{
    "success": false,
    "message": "Invalid credentials",
    "error_code": "INVALID_PASSWORD"
}
```

**Error Response (403 Forbidden - Account Inactive):**
```json
{
    "success": false,
    "message": "Account is not active",
    "error_code": "ACCOUNT_INACTIVE"
}
```

---

### 3. Get Current User
**GET** `/auth/me`

Get authenticated user's profile information.

**Headers:**
```
Authorization: Bearer <token>
```

**Success Response (200 OK):**
```json
{
    "success": true,
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "role": "user",
            "status": "active",
            "permissions": [
                "profile.read",
                "profile.update"
            ]
        }
    }
}
```

---

### 4. Logout
**POST** `/auth/logout`

Invalidate current JWT token.

**Headers:**
```
Authorization: Bearer <token>
```

**Success Response (200 OK):**
```json
{
    "success": true,
    "message": "Successfully logged out"
}
```

---

### 5. Refresh Token
**POST** `/auth/refresh`

Get a new JWT token using the current valid token.

**Headers:**
```
Authorization: Bearer <token>
```

**Success Response (200 OK):**
```json
{
    "success": true,
    "message": "Token refreshed successfully",
    "data": {
        "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
        "token_type": "bearer",
        "expires_in": 3600
    }
}
```

**Error Response (401 Unauthorized):**
```json
{
    "success": false,
    "message": "Failed to refresh token",
    "error": "Token has been invalidated"
}
```

---

## Protected Endpoints

### Admin Only Endpoints
**GET** `/admin/users`

Requires `admin` role.

**Headers:**
```
Authorization: Bearer <token>
```

**Success Response (200 OK):**
```json
{
    "success": true,
    "message": "Admin access granted - user list",
    "data": []
}
```

**Error Response (403 Forbidden):**
```json
{
    "success": false,
    "message": "Access denied. Insufficient permissions.",
    "error_code": "INSUFFICIENT_PERMISSIONS"
}
```

---

### Settings Endpoint
**GET** `/settings`

Requires `system.settings.read` permission.

**Headers:**
```
Authorization: Bearer <token>
```

**Success Response (200 OK):**
```json
{
    "success": true,
    "message": "Settings accessible"
}
```

---

### Users List Endpoint
**GET** `/users`

Requires `users.read` permission (admin only).

**Headers:**
```
Authorization: Bearer <token>
```

---

### Profile Endpoint
**GET** `/profile`

Authenticated user profile (any valid token).

**Headers:**
```
Authorization: Bearer <token>
```

---

## Error Codes

| Error Code | HTTP Status | Description |
|------------|-------------|-------------|
| `TOKEN_BLACKLISTED` | 401 | Token has been invalidated (logged out) |
| `TOKEN_EXPIRED` | 401 | Token has expired |
| `TOKEN_INVALID` | 401 | Token signature is invalid |
| `TOKEN_ABSENT` | 401 | No token provided |
| `USER_NOT_FOUND` | 401 | User associated with token not found |
| `ACCOUNT_INACTIVE` | 403 | User account is not active |
| `INSUFFICIENT_PERMISSIONS` | 403 | User role does not have required permission |
| `PERMISSION_DENIED` | 403 | User lacks specific permission |
| `VALIDATION_ERROR` | 422 | Request validation failed |

---

## Authentication Flow

1. **Registration/Login**: Client sends credentials to `/auth/login`
2. **Token Reception**: Server returns JWT token in response
3. **Authenticated Requests**: Client includes token in `Authorization: Bearer <token>` header
4. **Token Refresh**: When token expires, client calls `/auth/refresh` with current token
5. **Logout**: Client calls `/auth/logout` to invalidate current token

---

## JWT Token Structure

The JWT token contains the following claims:

```json
{
    "iss": "demo1-minimax",
    "iat": 1711305600,
    "exp": 1711309200,
    "nbf": 1711305600,
    "jti": "unique-token-id",
    "sub": 1,
    "prf": "user",
    "email": "john@example.com"
}
```

| Claim | Description |
|-------|-------------|
| `iss` | Issuer - Application name |
| `iat` | Issued at - Token creation timestamp |
| `exp` | Expiration - Token expiry timestamp |
| `nbf` | Not before - Token activation time |
| `jti` | JWT ID - Unique token identifier |
| `sub` | Subject - User ID |
| `prf` | Role - User role |
| `email` | User email |

---

## Rate Limiting

- Login attempts: 5 per minute per IP
- Token refresh: 10 per minute per user
- Registration: 3 per minute per IP

---

## Security Best Practices

1. **HTTPS Only**: Always use HTTPS in production
2. **Token Storage**: Store tokens securely on client side
3. **Password Requirements**: Minimum 6 characters
4. **Token Expiry**: Default 60 minutes (configurable)
5. **Refresh Window**: 2 weeks for refresh tokens
6. **Blacklisting**: Enabled for immediate token invalidation