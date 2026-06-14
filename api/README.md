# Advertisement API (PHP)

基于原生 PHP 的广告接口，用于从飞书多维表格获取赞助商广告数据，不再依赖 Node.js。

## 文件说明

- `ads.php`：广告列表与单条广告接口
- `health.php`：健康检查接口
- `common.php`：环境变量加载、飞书请求、令牌缓存、字段映射与过滤排序
- `.env.example`：环境变量模板

## 环境要求

- PHP 8.1 或更高版本
- 建议启用 `curl` 扩展
- Web 服务器可执行 `api/*.php`

## 配置

1. 在 `api` 目录复制环境变量模板：

```bash
cp .env.example .env
```

2. 填写飞书配置：

```env
FEISHU_APP_ID=your_app_id_here
FEISHU_APP_SECRET=your_app_secret_here
FEISHU_APP_TOKEN=your_app_token_here
FEISHU_TABLE_ID=your_table_id_here
ALLOWED_ORIGINS=http://localhost,https://srinternet.cn
```

## 接口

### 获取所有活跃广告

```http
GET /api/ads.php
```

响应示例：

```json
{
  "success": true,
  "count": 1,
  "data": [
    {
      "id": "ad-001",
      "title": "赞助商广告标题",
      "url": "https://example.com",
      "content": "广告描述",
      "imageUrl": "https://example.com/banner.jpg",
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

### 获取单条广告

```http
GET /api/ads.php?id=ad-001
```

也兼容 `PATH_INFO` 形式，例如 `/api/ads.php/ad-001`。

### 健康检查

```http
GET /api/health.php
```

响应示例：

```json
{
  "status": "ok",
  "timestamp": "2026-06-14T12:00:00+00:00",
  "feishuConfigured": true,
  "runtime": "php"
}
```

## 飞书字段支持

支持以下字段名变体：

- `id` / `ad_id`
- `title` / `ad_title`
- `url` / `ad_url` / `link`
- `content` / `ad_content` / `description`
- `imageUrl` / `image_url` / `ad_image`
- `tag` / `ad_tag` / `label`
- `type`
- `priority`
- `startDate` / `start_date` / `start_time`
- `endDate` / `end_date` / `end_time`
- `enabled`

## 处理逻辑

- 通过飞书开放平台接口获取 `tenant_access_token`
- 将令牌缓存到 `api/.feishu_token_cache.json`
- 分页读取多维表格记录
- 过滤未启用、缺少 `id/url`、超出投放时间的广告
- 按 `high > medium > low` 排序

## 部署提示

- Apache 或 Nginx + PHP-FPM 均可
- 若站点和接口同域部署，前端可直接请求 `/api/ads.php`
- 若跨域调用，请在 `ALLOWED_ORIGINS` 中写入允许的来源

## 故障排查

- 返回 `Feishu API not configured`：检查 `api/.env`
- 返回飞书鉴权失败：检查 `FEISHU_APP_ID` 与 `FEISHU_APP_SECRET`
- 无广告数据：检查多维表格字段、启用状态和投放时间
