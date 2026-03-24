# JWT认证系统 API 文档

## 目录
- [概述](#概述)
- [认证流程](#认证流程)
- [API接口](#api接口)
- [错误码说明](#错误码说明)
- [权限说明](#权限说明)
- [测试账户](#测试账户)

---

## 概述

本系统基于JWT（JSON Web Token）实现RESTful API认证服务，支持两种用户角色：普通用户(user)和管理员(admin)，每种角色拥有不同的权限集。

### 技术栈
- Laravel 12
- Firebase PHP-JWT
- MySQL
- bcrypt密码加密

### 令牌配置
| 令牌类型 | 默认有效期 | 环境变量 |
|---------|-----------|---------|
| Access Token | 1小时 (3600秒) | JWT_ACCESS_TOKEN_TTL |
| Refresh Token | 7天 (604800秒) | JWT_REFRESH_TOKEN_TTL |

---

## 认证流程

### 1. 登录流程
```
客户端                          服务器
   |                              |
   | POST /api/v1/auth/login      |
   | {email, password}            |
   |----------------------------->|
   |                              | 验证凭据
   |                              | 生成JWT令牌对
   | {access_token, refresh_token}|
   |<-----------------------------|
   |                              |
```

### 2. API请求流程
```
客户端                          服务器
   |                              |
   | GET /api/v1/protected        |
   | Authorization: Bearer {token}|
   |----------------------------->|
   |                              | 验证JWT签名
   |                              | 检查黑名单
   |                              | 验证权限
   | {响应数据}                    |
   |<-----------------------------|
   |                              |
```

### 3. 令牌刷新流程
```
客户端                          服务器
   |                              |
   | POST /api/v1/auth/refresh    |
   | {refresh_token}              |
   |----------------------------->|
   |                              | 验证refresh_token
   |                              | 将旧token加入黑名单
   |                              | 生成新的令牌对
   | {access_token, refresh_token}|
   |<-----------------------------|
   |                              |
```

---

## API接口

### 基础URL
```
http://localhost:8000/api/v1
```

---

### 1. 用户注册

**接口地址**: `POST /auth/register`

**认证要求**: 无

**请求参数**:
```json
{
    "name": "用户名",
    "email": "user@example.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```

**成功响应** (201 Created):
```json
{
    "success": true,
    "message": "注册成功",
    "data": {
        "user": {
            "id": 1,
            "name": "用户名",
            "email": "user@example.com",
            "role": "user",
            "permissions": ["view_own_profile", "edit_own_profile", "view_reports"]
        },
        "tokens": {
            "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
            "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
            "token_type": "Bearer",
            "access_expires_in": 3600,
            "refresh_expires_in": 604800
        }
    }
}
```

**错误响应** (422 Unprocessable Entity):
```json
{
    "message": "The email has already been taken.",
    "errors": {
        "email": ["The email has already been taken."]
    }
}
```

---

### 2. 用户登录

**接口地址**: `POST /auth/login`

**认证要求**: 无

**请求参数**:
```json
{
    "email": "user@example.com",
    "password": "password123"
}
```

**成功响应** (200 OK):
```json
{
    "success": true,
    "message": "登录成功",
    "data": {
        "user": {
            "id": 1,
            "name": "用户名",
            "email": "user@example.com",
            "role": "user",
            "permissions": ["view_own_profile", "edit_own_profile", "view_reports"]
        },
        "tokens": {
            "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
            "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
            "token_type": "Bearer",
            "access_expires_in": 3600,
            "refresh_expires_in": 604800
        }
    }
}
```

**错误响应**:
- 422: 凭据不正确
- 403: 账户已被禁用

---

### 3. 刷新令牌

**接口地址**: `POST /auth/refresh`

**认证要求**: 无

**请求参数**:
```json
{
    "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
}
```

**成功响应** (200 OK):
```json
{
    "success": true,
    "message": "令牌刷新成功",
    "data": {
        "tokens": {
            "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
            "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
            "token_type": "Bearer",
            "access_expires_in": 3600,
            "refresh_expires_in": 604800
        }
    }
}
```

**错误响应** (401 Unauthorized):
```json
{
    "success": false,
    "message": "无效的刷新令牌",
    "error_code": "INVALID_REFRESH_TOKEN"
}
```

---

### 4. 用户登出

**接口地址**: `POST /auth/logout`

**认证要求**: 需要 JWT Token

**请求头**:
```
Authorization: Bearer {access_token}
```

**成功响应** (200 OK):
```json
{
    "success": true,
    "message": "登出成功"
}
```

---

### 5. 获取当前用户信息

**接口地址**: `GET /auth/me`

**认证要求**: 需要 JWT Token

**请求头**:
```
Authorization: Bearer {access_token}
```

**成功响应** (200 OK):
```json
{
    "success": true,
    "data": {
        "user": {
            "id": 1,
            "name": "用户名",
            "email": "user@example.com",
            "role": "user",
            "permissions": ["view_own_profile", "edit_own_profile", "view_reports"]
        }
    }
}
```

---

### 6. 公开接口

**接口地址**: `GET /public`

**认证要求**: 无

**成功响应** (200 OK):
```json
{
    "success": true,
    "message": "这是一个公开的API端点",
    "data": {
        "timestamp": "2024-01-01T12:00:00+00:00"
    }
}
```

---

### 7. 受保护接口

**接口地址**: `GET /protected`

**认证要求**: 需要 JWT Token (任意角色)

**成功响应** (200 OK):
```json
{
    "success": true,
    "message": "这是一个受保护的API端点",
    "data": {
        "user": {
            "id": 1,
            "name": "用户名",
            "email": "user@example.com"
        },
        "timestamp": "2024-01-01T12:00:00+00:00"
    }
}
```

---

### 8. 普通用户专属接口

**接口地址**: `GET /user/dashboard`

**认证要求**: 需要 JWT Token + user角色

**成功响应** (200 OK):
```json
{
    "success": true,
    "message": "这是一个普通用户专属的API端点",
    "data": {
        "user": {
            "id": 2,
            "name": "Normal User",
            "role": "user"
        },
        "timestamp": "2024-01-01T12:00:00+00:00"
    }
}
```

---

### 9. 管理员专属接口

**接口地址**: `GET /admin/dashboard`

**认证要求**: 需要 JWT Token + admin角色

**成功响应** (200 OK):
```json
{
    "success": true,
    "message": "这是一个管理员专属的API端点",
    "data": {
        "user": {
            "id": 1,
            "name": "Admin User",
            "role": "admin"
        },
        "admin_data": {
            "total_users": 3,
            "active_users": 2
        },
        "timestamp": "2024-01-01T12:00:00+00:00"
    }
}
```

---

### 10. 权限控制接口

**接口地址**: `GET /reports`

**认证要求**: 需要 JWT Token + view_reports 或 manage_users 权限

**成功响应** (200 OK):
```json
{
    "success": true,
    "message": "这是一个需要特定权限的API端点",
    "data": {
        "user": {
            "id": 1,
            "name": "用户名",
            "permissions": ["view_own_profile", "edit_own_profile", "view_reports", ...]
        },
        "timestamp": "2024-01-01T12:00:00+00:00"
    }
}
```

---

## 错误码说明

| 错误码 | HTTP状态码 | 说明 |
|-------|-----------|------|
| TOKEN_NOT_PROVIDED | 401 | 未提供认证令牌 |
| INVALID_TOKEN | 401 | 无效或已过期的令牌 |
| INVALID_TOKEN_TYPE | 401 | 令牌类型错误 |
| USER_NOT_FOUND | 401 | 用户不存在 |
| ACCOUNT_DISABLED | 403 | 账户已被禁用 |
| INSUFFICIENT_ROLE | 403 | 权限不足（角色不匹配） |
| INSUFFICIENT_PERMISSIONS | 403 | 没有执行此操作的权限 |
| INVALID_REFRESH_TOKEN | 401 | 无效的刷新令牌 |
| UNAUTHENTICATED | 401 | 未认证的用户 |

---

## 权限说明

### 用户角色

| 角色 | 说明 |
|-----|------|
| user | 普通用户，拥有基本权限 |
| admin | 管理员，拥有所有权限 |

### 权限列表

| 权限名称 | 显示名称 | 描述 | user | admin |
|---------|---------|------|------|-------|
| view_users | 查看用户 | 允许查看用户列表 | ❌ | ✅ |
| create_users | 创建用户 | 允许创建新用户 | ❌ | ✅ |
| edit_users | 编辑用户 | 允许编辑用户信息 | ❌ | ✅ |
| delete_users | 删除用户 | 允许删除用户 | ❌ | ✅ |
| view_reports | 查看报告 | 允许查看系统报告 | ✅ | ✅ |
| manage_settings | 管理设置 | 允许管理系统设置 | ❌ | ✅ |
| manage_users | 管理用户 | 允许管理所有用户 | ❌ | ✅ |
| view_own_profile | 查看个人资料 | 允许查看自己的个人资料 | ✅ | ✅ |
| edit_own_profile | 编辑个人资料 | 允许编辑自己的个人资料 | ✅ | ✅ |

---

## 测试账户

| 邮箱 | 密码 | 角色 | 状态 |
|-----|------|------|------|
| admin@example.com | password123 | admin | 活跃 |
| user@example.com | password123 | user | 活跃 |
| inactive@example.com | password123 | user | 禁用 |

---

## 使用示例

### cURL 示例

```bash
# 登录
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"password123"}'

# 访问受保护接口
curl -X GET http://localhost:8000/api/v1/protected \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN"

# 访问管理员接口
curl -X GET http://localhost:8000/api/v1/admin/dashboard \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN"

# 登出
curl -X POST http://localhost:8000/api/v1/auth/logout \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN"
```

### JavaScript (fetch) 示例

```javascript
// 登录
const login = async () => {
  const response = await fetch('http://localhost:8000/api/v1/auth/login', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      email: 'admin@example.com',
      password: 'password123'
    })
  });
  const data = await response.json();
  localStorage.setItem('access_token', data.data.tokens.access_token);
  localStorage.setItem('refresh_token', data.data.tokens.refresh_token);
};

// 访问受保护接口
const getProtectedData = async () => {
  const token = localStorage.getItem('access_token');
  const response = await fetch('http://localhost:8000/api/v1/protected', {
    headers: { 'Authorization': `Bearer ${token}` }
  });
  return await response.json();
};

// 刷新令牌
const refreshToken = async () => {
  const refreshToken = localStorage.getItem('refresh_token');
  const response = await fetch('http://localhost:8000/api/v1/auth/refresh', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ refresh_token: refreshToken })
  });
  const data = await response.json();
  localStorage.setItem('access_token', data.data.tokens.access_token);
  localStorage.setItem('refresh_token', data.data.tokens.refresh_token);
};
```
