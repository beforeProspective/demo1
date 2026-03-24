# JWT认证流程说明

## 一、JWT令牌结构

JWT令牌由三部分组成，用点号(.)分隔：

```
header.payload.signature
```

### 1. Header（头部）
```json
{
    "alg": "HS256",
    "typ": "JWT"
}
```

### 2. Payload（载荷）

#### Access Token 载荷结构
```json
{
    "iss": "http://localhost",        // 签发者
    "iat": 1704067200,                // 签发时间
    "exp": 1704070800,                // 过期时间
    "jti": "abc123...",               // JWT ID（唯一标识）
    "sub": 1,                         // 用户ID
    "email": "user@example.com",      // 用户邮箱
    "name": "用户名",                  // 用户名
    "role": "user",                   // 用户角色
    "permissions": ["view_reports"],  // 权限列表
    "type": "access"                  // 令牌类型
}
```

#### Refresh Token 载荷结构
```json
{
    "iss": "http://localhost",
    "iat": 1704067200,
    "exp": 1704672000,
    "jti": "def456...",
    "sub": 1,
    "type": "refresh"
}
```

### 3. Signature（签名）
使用应用密钥（APP_KEY）对header和payload进行签名，确保令牌不被篡改。

---

## 二、认证流程详解

### 1. 用户登录流程

```
┌─────────┐                                    ┌─────────┐
│  客户端  │                                    │  服务器  │
└────┬────┘                                    └────┬────┘
     │                                              │
     │  1. POST /auth/login                         │
     │     {email, password}                        │
     │─────────────────────────────────────────────>│
     │                                              │
     │                           2. 验证邮箱和密码    │
     │                              - 查询用户       │
     │                              - bcrypt验证     │
     │                              - 检查账户状态   │
     │                                              │
     │                           3. 获取用户权限     │
     │                              - 从缓存读取     │
     │                              - 或从数据库查询 │
     │                                              │
     │                           4. 生成JWT令牌对    │
     │                              - 生成jti       │
     │                              - 构建payload   │
     │                              - 签名生成token │
     │                                              │
     │  5. 返回令牌对                               │
     │     {access_token, refresh_token}            │
     │<─────────────────────────────────────────────│
     │                                              │
     │  6. 存储令牌                                 │
     │     - localStorage/sessionStorage            │
     │     - 或内存中                               │
     │                                              │
```

### 2. API请求认证流程

```
┌─────────┐                                    ┌─────────┐
│  客户端  │                                    │  服务器  │
└────┬────┘                                    └────┬────┘
     │                                              │
     │  1. API请求                                  │
     │     Authorization: Bearer {access_token}     │
     │─────────────────────────────────────────────>│
     │                                              │
     │                           2. 提取Bearer Token │
     │                                              │
     │                           3. 解码JWT         │
     │                              - 验证签名      │
     │                              - 检查过期时间  │
     │                                              │
     │                           4. 检查黑名单       │
     │                              - 查询token_jti │
     │                              - 是否已失效    │
     │                                              │
     │                           5. 验证令牌类型     │
     │                              - 必须是access  │
     │                                              │
     │                           6. 获取用户信息     │
     │                              - 根据sub查询   │
     │                              - 检查is_active │
     │                                              │
     │                           7. 执行权限检查     │
     │                              - 角色验证      │
     │                              - 权限验证      │
     │                                              │
     │  8. 返回响应                                 │
     │<─────────────────────────────────────────────│
     │                                              │
```

### 3. 令牌刷新流程

```
┌─────────┐                                    ┌─────────┐
│  客户端  │                                    │  服务器  │
└────┬────┘                                    └────┬────┘
     │                                              │
     │  1. POST /auth/refresh                       │
     │     {refresh_token}                          │
     │─────────────────────────────────────────────>│
     │                                              │
     │                           2. 验证refresh_token│
     │                              - 解码验证      │
     │                              - 检查黑名单    │
     │                              - 验证类型      │
     │                                              │
     │                           3. 将旧token加入黑名单│
     │                              - 记录jti       │
     │                              - 设置过期时间  │
     │                                              │
     │                           4. 生成新令牌对     │
     │                                              │
     │  5. 返回新令牌对                             │
     │<─────────────────────────────────────────────│
     │                                              │
     │  6. 更新本地存储的令牌                        │
     │                                              │
```

### 4. 用户登出流程

```
┌─────────┐                                    ┌─────────┐
│  客户端  │                                    │  服务器  │
└────┬────┘                                    └────┬────┘
     │                                              │
     │  1. POST /auth/logout                        │
     │     Authorization: Bearer {access_token}     │
     │─────────────────────────────────────────────>│
     │                                              │
     │                           2. 验证access_token │
     │                                              │
     │                           3. 将token加入黑名单 │
     │                              - 记录jti       │
     │                              - reason=logout │
     │                                              │
     │  4. 返回成功                                 │
     │<─────────────────────────────────────────────│
     │                                              │
     │  5. 清除本地存储的令牌                        │
     │                                              │
```

---

## 三、安全机制

### 1. 密码安全
- 使用bcrypt算法加密存储
- 自动加盐处理
- 可配置加密轮数（BCRYPT_ROUNDS=12）

### 2. 令牌安全
- 使用HS256算法签名
- 每个令牌包含唯一标识（jti）
- 支持令牌黑名单机制
- 区分access_token和refresh_token

### 3. 黑名单机制
```
token_blacklist 表结构：
┌─────────────┬──────────────────────────────────────┐
│ 字段        │ 说明                                 │
├─────────────┼──────────────────────────────────────┤
│ id          │ 主键                                 │
│ token_jti   │ JWT唯一标识                          │
│ user_id     │ 用户ID                               │
│ expires_at  │ 令牌过期时间（用于清理过期记录）      │
│ reason      │ 失效原因（logout/refresh）           │
│ created_at  │ 创建时间                             │
└─────────────┴──────────────────────────────────────┘
```

### 4. 权限缓存
- 角色权限缓存1小时
- 减少数据库查询
- 自动更新机制

---

## 四、最佳实践

### 1. 客户端存储
```javascript
// 推荐：使用内存或httpOnly cookie
// 不推荐：localStorage（XSS风险）

// 安全的令牌存储示例
class TokenManager {
    constructor() {
        this.accessToken = null;
        this.refreshToken = null;
    }
    
    setTokens(access, refresh) {
        this.accessToken = access;
        this.refreshToken = refresh;
    }
    
    getAccessToken() {
        return this.accessToken;
    }
    
    clear() {
        this.accessToken = null;
        this.refreshToken = null;
    }
}
```

### 2. 自动刷新令牌
```javascript
const apiClient = async (url, options = {}) => {
    const token = tokenManager.getAccessToken();
    
    const response = await fetch(url, {
        ...options,
        headers: {
            ...options.headers,
            'Authorization': `Bearer ${token}`
        }
    });
    
    if (response.status === 401) {
        // 尝试刷新令牌
        const newTokens = await refreshToken();
        if (newTokens) {
            // 重试请求
            return fetch(url, {
                ...options,
                headers: {
                    ...options.headers,
                    'Authorization': `Bearer ${newTokens.access_token}`
                }
            });
        }
    }
    
    return response;
};
```

### 3. 请求拦截器示例（Axios）
```javascript
import axios from 'axios';

const api = axios.create({
    baseURL: '/api/v1'
});

// 请求拦截器
api.interceptors.request.use(config => {
    const token = localStorage.getItem('access_token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

// 响应拦截器
api.interceptors.response.use(
    response => response,
    async error => {
        if (error.response?.status === 401) {
            const refreshToken = localStorage.getItem('refresh_token');
            if (refreshToken) {
                try {
                    const { data } = await axios.post('/api/v1/auth/refresh', {
                        refresh_token: refreshToken
                    });
                    localStorage.setItem('access_token', data.data.tokens.access_token);
                    localStorage.setItem('refresh_token', data.data.tokens.refresh_token);
                    
                    error.config.headers.Authorization = `Bearer ${data.data.tokens.access_token}`;
                    return axios(error.config);
                } catch (refreshError) {
                    // 刷新失败，跳转登录
                    localStorage.clear();
                    window.location.href = '/login';
                }
            }
        }
        return Promise.reject(error);
    }
);
```

---

## 五、错误处理

### 1. 令牌过期处理
```javascript
// 检测令牌即将过期
const isTokenExpiringSoon = (token) => {
    const payload = JSON.parse(atob(token.split('.')[1]));
    const expiresAt = payload.exp * 1000;
    const now = Date.now();
    const fiveMinutes = 5 * 60 * 1000;
    return expiresAt - now < fiveMinutes;
};

// 主动刷新
if (isTokenExpiringSoon(accessToken)) {
    await refreshToken();
}
```

### 2. 错误码处理
```javascript
const handleAuthError = (error) => {
    const errorCode = error.response?.data?.error_code;
    
    switch (errorCode) {
        case 'TOKEN_NOT_PROVIDED':
        case 'INVALID_TOKEN':
        case 'INVALID_TOKEN_TYPE':
            // 需要重新登录
            redirectToLogin();
            break;
        case 'ACCOUNT_DISABLED':
            // 账户被禁用
            showAccountDisabledMessage();
            break;
        case 'INSUFFICIENT_ROLE':
        case 'INSUFFICIENT_PERMISSIONS':
            // 权限不足
            showPermissionDeniedMessage();
            break;
        default:
            // 其他错误
            showGenericError();
    }
};
```

---

## 六、配置说明

### 环境变量
```env
# JWT令牌有效期配置
JWT_ACCESS_TOKEN_TTL=3600      # 访问令牌有效期（秒）
JWT_REFRESH_TOKEN_TTL=604800   # 刷新令牌有效期（秒）

# 密码加密配置
BCRYPT_ROUNDS=12               # bcrypt加密轮数

# 应用密钥（用于JWT签名）
APP_KEY=base64:xxx             # 自动生成，勿修改
```

### 配置文件
```php
// config/jwt.php
return [
    'access_token_ttl' => env('JWT_ACCESS_TOKEN_TTL', 3600),
    'refresh_token_ttl' => env('JWT_REFRESH_TOKEN_TTL', 604800),
];
```
