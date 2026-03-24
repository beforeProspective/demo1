# JWT Authentication Flow & User Permissions

## Authentication Flow

### 1. Registration Flow

```
┌──────────┐                      ┌──────────┐                      ┌──────────┐
│  Client  │                      │   API    │                      │Database  │
└────┬─────┘                      └────┬─────┘                      └────┬─────┘
     │                                   │                                 │
     │  POST /api/auth/register         │                                 │
     │  {name, email, password}          │                                 │
     │──────────────────────────────────>│                                 │
     │                                   │                                 │
     │                                   │  Validate input                  │
     │                                   │────────────────────────────────>│
     │                                   │                                 │
     │                                   │  Check if email exists          │
     │                                   │────────────────────────────────>│
     │                                   │                                 │
     │                                   │  Hash password (bcrypt)         │
     │                                   │                                 │
     │                                   │  Create user with role='user'   │
     │                                   │────────────────────────────────>│
     │                                   │                                 │
     │                                   │  Generate JWT token              │
     │                                   │  (contains: sub, role, email)   │
     │                                   │                                 │
     │  201 Created                     │                                 │
     │  {user, token, expires_in}       │                                 │
     │<──────────────────────────────────│                                 │
     │                                   │                                 │
```

### 2. Login Flow

```
┌──────────┐                      ┌──────────┐                      ┌──────────┐
│  Client  │                      │   API    │                      │Database  │
└────┬─────┘                      └────┬─────┘                      └────┬─────┘
     │                                   │                                 │
     │  POST /api/auth/login             │                                 │
     │  {email, password}                │                                 │
     │──────────────────────────────────>│                                 │
     │                                   │                                 │
     │                                   │  Find user by email             │
     │                                   │────────────────────────────────>│
     │                                   │                                 │
     │                                   │  Verify password (bcrypt)      │
     │                                   │                                 │
     │                                   │  Check account status           │
     │                                   │────────────────────────────────>│
     │                                   │                                 │
     │                                   │  Generate JWT token             │
     │                                   │                                 │
     │  200 OK                          │                                 │
     │  {user, token, expires_in}       │                                 │
     │<──────────────────────────────────│                                 │
     │                                   │                                 │
```

### 3. Authenticated Request Flow

```
┌──────────┐                      ┌──────────┐                      ┌──────────┐
│  Client  │                      │   API    │                      │Database  │
└────┬─────┘                      └────┬─────┘                      └────┬─────┘
     │                                   │                                 │
     │  GET /api/profile                 │                                 │
     │  Authorization: Bearer <token>    │                                 │
     │──────────────────────────────────>│                                 │
     │                                   │                                 │
     │                                   │  Extract & Validate JWT         │
     │                                   │  Check blacklist               │
     │                                   │────────────────────────────────>│
     │                                   │                                 │
     │                                   │  Check user status              │
     │                                   │────────────────────────────────>│
     │                                   │                                 │
     │                                   │  Execute request                │
     │                                   │                                 │
     │  200 OK                          │                                 │
     │  {user, profile_data}             │                                 │
     │<──────────────────────────────────│                                 │
     │                                   │                                 │
```

### 4. Token Refresh Flow

```
┌──────────┐                      ┌──────────┐                      ┌──────────┐
│  Client  │                      │   API    │                      │Database  │
└────┬─────┘                      └────┬─────┘                      └────┬─────┘
     │                                   │                                 │
     │  POST /api/auth/refresh           │                                 │
     │  Authorization: Bearer <token>    │                                 │
     │──────────────────────────────────>│                                 │
     │                                   │                                 │
     │                                   │  Blacklist old token            │
     │                                   │────────────────────────────────>│
     │                                   │                                 │
     │                                   │  Generate new JWT token         │
     │                                   │                                 │
     │  200 OK                          │                                 │
     │  {new_token, expires_in}          │                                 │
     │<──────────────────────────────────│                                 │
     │                                   │                                 │
```

### 5. Logout Flow

```
┌──────────┐                      ┌──────────┐                      ┌──────────┐
│  Client  │                      │   API    │                      │Database  │
└────┬─────┘                      └────┬─────┘                      └────┬─────┘
     │                                   │                                 │
     │  POST /api/auth/logout            │                                 │
     │  Authorization: Bearer <token>    │                                 │
     │──────────────────────────────────>│                                 │
     │                                   │                                 │
     │                                   │  Add token to blacklist         │
     │                                   │────────────────────────────────>│
     │                                   │                                 │
     │                                   │  Invalidate token in cache      │
     │                                   │                                 │
     │  200 OK                          │                                 │
     │  {message: "Logged out"}         │                                 │
     │<──────────────────────────────────│                                 │
     │                                   │                                 │
```

---

## User Types & Permissions

### User Roles

| Role | Description |
|------|-------------|
| `user` | Regular end-user with basic permissions |
| `admin` | Administrator with full system access |

### Permission Matrix

| Permission | user | admin |
|------------|------|-------|
| `profile.read` | ✅ | ✅ |
| `profile.update` | ✅ | ✅ |
| `users.read` | ❌ | ✅ |
| `users.create` | ❌ | ✅ |
| `users.update` | ❌ | ✅ |
| `users.delete` | ❌ | ✅ |
| `system.settings.read` | ❌ | ✅ |
| `system.settings.update` | ❌ | ✅ |

---

## Middleware Architecture

### JWT Authentication Middleware (`jwt.auth`)

This middleware intercepts all protected requests and performs:

1. **Token Extraction**: Extracts JWT from `Authorization: Bearer <token>` header
2. **Token Validation**: Verifies token signature and expiration
3. **Blacklist Check**: Queries `jwt_blacklist` table for invalidated tokens
4. **User Verification**: Confirms user exists and is active
5. **Context Setup**: Sets authenticated user in request context

### Role-Based Access Control Middleware (`role`)

```php
Route::middleware(['jwt.auth', 'role:admin'])->group(function () {
    // Admin-only routes
});
```

Checks if authenticated user's `role` field matches required role(s).

### Permission-Based Access Control Middleware (`permission`)

```php
Route::middleware(['jwt.auth', 'permission:users.read'])->group(function () {
    // Routes requiring specific permission
});
```

Checks if authenticated user has required permission(s) based on their role.

---

## Database Schema

### Users Table

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT | PK, AUTO_INCREMENT | Unique identifier |
| name | VARCHAR(255) | NOT NULL | User's full name |
| email | VARCHAR(255) | UNIQUE, NOT NULL | User's email address |
| role | ENUM | DEFAULT 'user' | 'user' or 'admin' |
| status | ENUM | DEFAULT 'active' | 'active', 'inactive', 'banned' |
| password | VARCHAR(255) | NOT NULL | Hashed password (bcrypt) |
| email_verified_at | TIMESTAMP | NULLABLE | Email verification timestamp |
| remember_token | VARCHAR(100) | NULLABLE | Remember me token |
| created_at | TIMESTAMP | | Record creation time |
| updated_at | TIMESTAMP | | Record update time |

### JWT Blacklist Table

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT | PK, AUTO_INCREMENT | Unique identifier |
| token_id | VARCHAR(255) | UNIQUE | JWT ID (jti claim) |
| token | VARCHAR(500) | NOT NULL | The actual JWT token |
| expires_at | TIMESTAMP | NOT NULL | Token expiration time |
| blacklisted_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | When token was blacklisted |
| user_id | BIGINT | FK -> users.id | Associated user |

**Indexes:**
- `token_id` (unique)
- `(token_id, user_id)`

---

## Security Implementation

### Password Security
- **Algorithm**: bcrypt (via Laravel's `Hash::make()`)
- **Rounds**: 12 (configured in `.env`)
- **Verification**: `Hash::check()` for secure comparison

### JWT Security
- **Algorithm**: HS256 (HMAC SHA-256)
- **Secret**: Generated via `php artisan jwt:secret`
- **TTL**: 60 minutes (configurable)
- **Refresh Window**: 2 weeks
- **Blacklist**: Enabled for immediate invalidation

### Token Payload

```json
{
    "iss": "demo1-minimax",      // Issuer
    "iat": 1711305600,           // Issued at
    "exp": 1711309200,           // Expiration
    "nbf": 1711305600,           // Not before
    "jti": "abc123...",          // JWT ID (unique)
    "sub": 1,                    // User ID
    "prf": "user",               // Role
    "email": "user@example.com"  // User email
}
```

---

## Status Codes

| Status | Meaning |
|--------|---------|
| `active` | User can login and access system |
| `inactive` | User cannot login, may be reactivated |
| `banned` | User permanently banned from system |

---

## Testing Checklist

### User Role Tests
- [ ] Regular user can login
- [ ] Regular user can access profile
- [ ] Regular user CANNOT access admin endpoints
- [ ] Admin user can login
- [ ] Admin user can access all endpoints
- [ ] Admin user CANNOT access non-existent permissions

### Token Tests
- [ ] Valid token grants access
- [ ] Expired token is rejected
- [ ] Invalid signature is rejected
- [ ] Blacklisted token is rejected
- [ ] Token refresh works correctly
- [ ] Logout invalidates token

### Status Tests
- [ ] Active user can login
- [ ] Inactive user cannot login
- [ ] Banned user cannot login