# 广告系统文档 (Advertisement System Documentation)

## 概述 (Overview)

该广告系统支持在官网底部展示赞助商广告，通过飞书多维表格API动态获取广告内容，具备以下特性：
- ✅ **已实现飞书多维表格API集成** - 通过后端服务安全获取广告数据
- 🔐 安全的凭证管理 - API密钥存储在服务器端
- 📊 支持多种广告类型（iframe、banner、card）
- 🎯 优先级管理（high、medium、low）
- 📅 基于日期的自动隐藏功能
- 💾 用户可关闭广告（session级别记忆）
- 🔄 自动降级到备用广告（当API不可用时）

## 架构 (Architecture)

```
前端 (Frontend)              后端 (Backend API)           飞书 (Feishu)
┌──────────────┐            ┌─────────────────┐         ┌──────────────┐
│  script.js   │  ───────>  │   server.js     │  ────>  │ 多维表格 API  │
│              │  /api/ads  │  (Node.js)      │  Auth   │              │
│ 展示广告      │  <───────  │  处理数据        │  <────  │  广告数据     │
└──────────────┘            └─────────────────┘         └──────────────┘
```

## 快速开始 (Quick Start)

### 1. 配置后端API

进入 `api` 目录并安装依赖：

```bash
cd api
npm install
cp .env.example .env
```

编辑 `.env` 文件，填入飞书应用凭证：

```env
FEISHU_APP_ID=your_app_id_here
FEISHU_APP_SECRET=your_app_secret_here
FEISHU_APP_TOKEN=your_app_token_here
FEISHU_TABLE_ID=your_table_id_here
PORT=3000
ALLOWED_ORIGINS=http://localhost,https://srinternet.cn
```

启动服务器：

```bash
npm start
```

### 2. 配置前端

前端会自动从 `/api/ads` 端点获取广告数据。如果API不可用，将自动使用备用广告。

在 `script.js` 中可以配置：

```javascript
const adConfig = {
  enabled: true,
  apiEndpoint: '/api/ads',  // 设置为 null 禁用API
  expiryDate: new Date('2026-02-27T23:59:59+08:00'),
  fallbackAd: { /* 备用广告配置 */ }
};
```

## 广告类型 (Ad Types)

### 1. iframe 类型
嵌入完整的网页内容，适合展示交互式广告页面。

```javascript
{
  type: 'iframe',
  url: 'https://example.com'
}
```

### 2. banner 类型
展示单张横幅图片，点击跳转到目标链接。

```javascript
{
  type: 'banner',
  url: 'https://example.com',
  imageUrl: 'https://example.com/banner.jpg'
}
```

### 3. card 类型
展示卡片式广告，包含图片、标题、描述和按钮。

```javascript
{
  type: 'card',
  url: 'https://example.com',
  imageUrl: 'https://example.com/image.jpg',
  title: '广告标题',
  description: '广告描述文字'
}
```

## 优先级 (Priority Levels)

- **high**: 高优先级，带有紫色渐变背景强调
- **medium**: 中优先级，带有青色边框
- **low**: 低优先级，略微透明显示

## 飞书多维表格配置 (Feishu Table Configuration)

### 必需字段 (Required Fields)

在飞书多维表格中创建以下字段：

| 字段名 | 字段类型 | 说明 | 必填 | 示例 |
|--------|----------|------|------|------|
| `id` 或 `ad_id` | 文本 | 广告唯一标识 | ✅ | `ad-001` |
| `title` 或 `ad_title` | 文本 | 广告标题 | ⭕ | `赞助商广告` |
| `url` 或 `ad_url` 或 `link` | URL | 广告链接 | ✅ | `https://example.com` |
| `content` 或 `ad_content` | 文本 | 广告内容描述 | ⭕ | `感谢赞助商支持` |
| `imageUrl` 或 `image_url` 或 `ad_image` | URL | 广告图片链接 | ⭕ | `https://example.com/img.jpg` |
| `tag` 或 `ad_tag` 或 `label` | 文本 | 广告标签 | ⭕ | `赞助商` |
| `type` | 单选 | 广告类型 | ⭕ | `card` (默认) |
| `priority` | 单选 | 优先级 | ⭕ | `high` / `medium` / `low` |
| `startDate` 或 `start_date` | 日期 | 开始时间 | ⭕ | `2026-01-01` |
| `endDate` 或 `end_date` | 日期 | 截止时间 | ⭕ | `2026-12-31` |
| `enabled` | 复选框 | 是否启用 | ⭕ | `true` (默认) |

### 字段详细说明

#### type（广告类型）
- `iframe`: 嵌入完整网页
- `banner`: 横幅图片广告（需要 imageUrl）
- `card`: 卡片式广告（支持图片、标题、描述）

#### priority（优先级）
- `high`: 高优先级，优先展示
- `medium`: 中等优先级
- `low`: 低优先级

### 获取飞书配置参数

详细步骤请参考 `api/README.md` 文档。

简要说明：
1. **App ID & Secret**: 在飞书开放平台创建应用获取
2. **App Token**: 多维表格URL中 `/base/` 后面的部分
3. **Table ID**: 多维表格URL中表格的ID部分
4. **权限**: 需要申请 `bitable:app` 或 `bitable:app:readonly` 权限

## 数据流程 (Data Flow)

1. **前端初始化**: 页面加载时调用 `initAdSystem()`
2. **获取数据**: 前端请求 `/api/ads` 端点
3. **后端处理**: 
   - 获取飞书访问令牌（自动缓存）
   - 从多维表格获取记录（支持分页）
   - 处理和验证数据
   - 按优先级排序
4. **前端展示**:
   - 过滤已关闭的广告
   - 检查日期范围
   - 渲染广告内容
5. **降级处理**: API失败时使用备用广告

## API 端点 (API Endpoints)

### GET /api/ads
获取所有活跃广告（已按优先级排序）

**响应格式**:
```json
{
  "success": true,
  "count": 2,
  "data": [
    {
      "id": "ad-001",
      "title": "赞助商广告标题",
      "url": "https://example.com",
      "content": "广告内容描述",
      "imageUrl": "https://example.com/image.jpg",
      "tag": "赞助商",
      "type": "card",
      "priority": "high",
      "startDate": "2026-01-01T00:00:00+08:00",
      "endDate": "2026-12-31T23:59:59+08:00",
      "enabled": true
    }
  ]
}
```

### GET /api/ads/:id
获取特定广告详情

### GET /health
健康检查端点

## 测试建议 (Testing Recommendations)

1. **API测试**:
   ```bash
   # 测试健康检查
   curl http://localhost:3000/health
   
   # 测试获取广告
   curl http://localhost:3000/api/ads
   ```

2. **日期测试**: 修改系统时间验证日期过滤
3. **类型测试**: 在飞书表格中添加不同类型的广告测试
4. **优先级测试**: 验证高优先级广告优先展示
5. **关闭功能**: 测试关闭按钮和sessionStorage
6. **降级测试**: 关闭API服务器，验证是否使用备用广告
7. **响应式测试**: 在不同屏幕尺寸下测试布局

## 部署指南 (Deployment Guide)

### 开发环境

```bash
# 启动后端API
cd api
npm install
npm start

# 在另一个终端启动前端（如果使用本地服务器）
cd ..
python -m http.server 8000
# 或使用其他本地服务器
```

### 生产环境

#### 方式1: 使用 PM2

```bash
cd api
npm install -g pm2
pm2 start server.js --name "ad-api"
pm2 startup
pm2 save
```

#### 方式2: 使用 Docker

在 `api` 目录创建 `Dockerfile`：

```dockerfile
FROM node:18-alpine
WORKDIR /app
COPY package*.json ./
RUN npm ci --only=production
COPY . .
EXPOSE 3000
CMD ["npm", "start"]
```

构建和运行：
```bash
docker build -t ad-api ./api
docker run -d -p 3000:3000 --env-file ./api/.env ad-api
```

#### 方式3: Nginx 反向代理

```nginx
# 代理API请求
location /api/ {
    proxy_pass http://localhost:3000/api/;
    proxy_http_version 1.1;
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
}

# 静态文件
location / {
    root /var/www/html;
    try_files $uri $uri/ =404;
}
```

## 安全考虑 (Security Considerations)

1. ✅ **凭证安全**: API密钥存储在后端 `.env` 文件中，不暴露给前端
2. ✅ **iframe沙箱**: iframe配置了sandbox属性，限制潜在风险
3. ✅ **CORS保护**: 后端API配置了CORS，只允许特定域名访问
4. ✅ **输入验证**: 后端对飞书数据进行验证和过滤
5. ⚠️ **HTTPS**: 生产环境必须使用HTTPS
6. ⚠️ **速率限制**: 建议在生产环境添加API速率限制
7. ⚠️ **日志监控**: 记录API访问日志，监控异常行为
8. ⚠️ **定期更新**: 定期更新依赖包，运行 `npm audit`

## 故障排除 (Troubleshooting)

### 问题1: API返回错误
**症状**: 前端显示备用广告，控制台有错误信息

**解决方案**:
1. 检查后端服务是否运行: `curl http://localhost:3000/health`
2. 查看后端日志: `pm2 logs ad-api` 或 `docker logs <container_id>`
3. 验证飞书凭证是否正确配置在 `.env` 文件中
4. 确认飞书应用有足够的权限

### 问题2: 广告不显示
**症状**: 页面加载正常，但没有广告

**可能原因**:
1. 所有广告的 `enabled` 字段为 `false`
2. 广告不在日期范围内
3. 用户已关闭该广告（检查 sessionStorage）
4. 广告已过全局过期日期（2026-02-27）

**解决方案**:
- 在飞书表格中检查广告状态
- 清除浏览器 sessionStorage
- 查看浏览器控制台是否有错误信息

### 问题3: CORS错误
**症状**: 浏览器控制台显示跨域错误

**解决方案**:
在 `api/.env` 中添加你的域名到 `ALLOWED_ORIGINS`:
```env
ALLOWED_ORIGINS=http://localhost,https://yourdomain.com
```

### 问题4: 飞书API调用失败
**症状**: 后端日志显示飞书API错误

**解决方案**:
1. 验证 App ID 和 App Secret
2. 确认 App Token 和 Table ID 正确
3. 检查飞书应用权限配置
4. 查看飞书开放平台是否有API限流

## 更新日志 (Changelog)

### 2026-02-12
- ✅ 实现完整的飞书多维表格API集成
- ✅ 创建独立的后端API服务器（Node.js + Express）
- ✅ 添加访问令牌缓存和自动刷新
- ✅ 实现分页支持，可获取大量广告记录
- ✅ 添加数据验证和过滤逻辑
- ✅ 实现优先级排序
- ✅ 添加降级机制，API失败时使用备用广告
- ✅ 更新前端代码，支持从API获取多个广告
- ✅ 完善文档和部署指南

### 2026-02-11
- 初始版本发布
- 支持iframe类型广告展示gift.coludai.cn
- 实现日期自动隐藏（2026-02-27到期）
- 添加用户关闭功能
- 预留飞书API集成接口
