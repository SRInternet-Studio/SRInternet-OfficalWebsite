# Advertisement API Server

基于 Node.js 和 Express 的广告 API 服务器，用于从飞书多维表格获取广告数据。

## 功能特性

- 🔐 安全的飞书 API 集成（凭证存储在服务器端）
- 📊 从飞书多维表格获取广告数据
- ⚡ 访问令牌自动缓存和刷新
- 🔄 支持分页获取大量记录
- ✅ 自动过滤和验证广告数据
- 📈 按优先级排序广告
- 🌐 CORS 支持
- 🏥 健康检查端点

## 安装

1. 进入 API 目录：
```bash
cd api
```

2. 安装依赖：
```bash
npm install
```

3. 配置环境变量：
```bash
cp .env.example .env
```

4. 编辑 `.env` 文件，填入你的飞书应用凭证：
```env
FEISHU_APP_ID=your_app_id_here
FEISHU_APP_SECRET=your_app_secret_here
FEISHU_APP_TOKEN=your_app_token_here
FEISHU_TABLE_ID=your_table_id_here
PORT=3000
ALLOWED_ORIGINS=http://localhost,https://srinternet.cn
```

## 飞书多维表格配置

### 必需字段

在飞书多维表格中，请确保包含以下字段：

| 字段名 | 类型 | 说明 | 是否必填 |
|--------|------|------|----------|
| `id` 或 `ad_id` | 文本 | 广告唯一标识 | 是 |
| `title` 或 `ad_title` | 文本 | 广告标题 | 否 |
| `url` 或 `ad_url` 或 `link` | URL | 广告链接 | 是 |
| `content` 或 `ad_content` 或 `description` | 文本 | 广告内容描述 | 否 |
| `imageUrl` 或 `image_url` 或 `ad_image` | URL | 广告图片链接 | 否 |
| `tag` 或 `ad_tag` 或 `label` | 文本 | 广告标签 | 否 |
| `type` | 单选 | 广告类型: iframe/banner/card | 否（默认 card） |
| `priority` | 单选 | 优先级: high/medium/low | 否（默认 medium） |
| `startDate` 或 `start_date` 或 `start_time` | 日期 | 开始时间 | 否 |
| `endDate` 或 `end_date` 或 `end_time` | 日期 | 截止时间 | 否 |
| `enabled` | 复选框 | 是否启用 | 否（默认 true） |

### 获取飞书配置参数

1. **App ID 和 App Secret**
   - 登录飞书开放平台：https://open.feishu.cn/
   - 创建企业自建应用
   - 在应用详情页面获取 App ID 和 App Secret

2. **App Token**
   - 打开你的飞书多维表格
   - 在浏览器地址栏中，App Token 是 URL 中 `/base/` 后面的部分
   - 例如：`https://xxx.feishu.cn/base/APP_TOKEN/...`

3. **Table ID**
   - 在多维表格中，Table ID 是 URL 中 `...base/xxx/` 后面的部分
   - 例如：`https://xxx.feishu.cn/base/xxx/TABLE_ID`

4. **配置权限**
   - 在飞书开放平台，为你的应用添加权限：
     - `bitable:app` - 查看、创建和更新多维表格
     - 或者至少需要 `bitable:app:readonly` - 只读访问

## 运行

### 开发模式（带自动重启）：
```bash
npm run dev
```

### 生产模式：
```bash
npm start
```

服务器将在配置的端口启动（默认 3000）。

## API 端点

### 获取所有活跃广告
```http
GET /api/ads
```

**响应示例：**
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

### 获取特定广告
```http
GET /api/ads/:id
```

**响应示例：**
```json
{
  "success": true,
  "data": {
    "id": "ad-001",
    "title": "赞助商广告标题",
    "url": "https://example.com",
    ...
  }
}
```

### 健康检查
```http
GET /health
```

**响应示例：**
```json
{
  "status": "ok",
  "timestamp": "2026-02-12T10:00:00.000Z",
  "feishuConfigured": true
}
```

## 错误处理

API 会返回适当的 HTTP 状态码和错误信息：

- `200` - 成功
- `404` - 资源未找到
- `500` - 服务器错误

错误响应格式：
```json
{
  "error": "错误类型",
  "message": "详细错误信息"
}
```

## 数据过滤逻辑

API 自动过滤广告，只返回满足以下条件的广告：

1. `enabled` 字段为 `true`
2. 必须有 `id` 和 `url` 字段
3. 当前时间在 `startDate` 和 `endDate` 范围内（如果设置了日期）

## 部署建议

### 使用 PM2 部署

1. 安装 PM2：
```bash
npm install -g pm2
```

2. 启动应用：
```bash
pm2 start server.js --name "ad-api"
```

3. 设置开机自启：
```bash
pm2 startup
pm2 save
```

### 使用 Docker 部署

创建 `Dockerfile`：
```dockerfile
FROM node:18-alpine
WORKDIR /app
COPY package*.json ./
RUN npm ci --only=production
COPY . .
EXPOSE 3000
CMD ["npm", "start"]
```

构建并运行：
```bash
docker build -t ad-api .
docker run -p 3000:3000 --env-file .env ad-api
```

### 使用 Nginx 反向代理

```nginx
location /api/ {
    proxy_pass http://localhost:3000/api/;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection 'upgrade';
    proxy_set_header Host $host;
    proxy_cache_bypass $http_upgrade;
}
```

## 安全建议

1. **不要提交 .env 文件** - 已在 .gitignore 中排除
2. **使用 HTTPS** - 在生产环境中始终使用 HTTPS
3. **限制 CORS 源** - 仅允许你的域名访问 API
4. **定期更新依赖** - 运行 `npm audit` 检查安全漏洞
5. **使用环境变量** - 所有敏感配置应通过环境变量传递
6. **限制请求速率** - 考虑添加速率限制中间件

## 故障排除

### 问题：访问令牌失效
**解决方案**：API 会自动刷新令牌，但如果持续失败，请检查 App ID 和 App Secret 是否正确。

### 问题：无法获取表格数据
**解决方案**：
1. 检查 App Token 和 Table ID 是否正确
2. 确认应用有足够的权限
3. 确认多维表格对应用可见

### 问题：CORS 错误
**解决方案**：在 `.env` 文件中添加你的前端域名到 `ALLOWED_ORIGINS`。

## 许可证

MIT License - 详见根目录 LICENSE 文件
