# 广告系统文档 (Advertisement System Documentation)

## 概述 (Overview)

该广告系统支持在官网底部展示赞助商广告，具备以下特性：
- 基于日期的自动隐藏功能
- 支持多种广告类型（iframe、banner、card）
- 优先级管理（high、medium、low）
- 用户可关闭广告（session级别记忆）
- 预留飞书多维表格API集成接口

## 当前配置 (Current Configuration)

当前广告配置在 `script.js` 的 `adConfig` 对象中：

```javascript
const adConfig = {
  enabled: true,
  expiryDate: new Date('2026-02-27T23:59:59+08:00'),
  currentAd: {
    id: 'sponsor-gift-coludai',
    type: 'iframe',
    priority: 'high',
    url: 'https://gift.coludai.cn',
    title: '赞助商 - Gift Coludai',
    description: '感谢赞助商的支持',
    startDate: new Date('2026-01-01T00:00:00+08:00'),
    endDate: new Date('2026-02-27T23:59:59+08:00')
  }
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

## 飞书API集成指南 (Feishu API Integration Guide)

### 数据结构要求

飞书多维表格应包含以下字段：

| 字段名 | 类型 | 说明 | 必填 |
|--------|------|------|------|
| id | 文本 | 广告唯一标识 | 是 |
| type | 单选 | 广告类型: iframe/banner/card | 是 |
| priority | 单选 | 优先级: high/medium/low | 是 |
| url | URL | 目标链接 | 是 |
| title | 文本 | 广告标题 | 否 |
| description | 文本 | 广告描述 | 否 |
| imageUrl | URL | 图片链接（banner和card类型需要） | 否 |
| startDate | 日期 | 开始展示日期 | 否 |
| endDate | 日期 | 结束展示日期 | 否 |
| enabled | 复选框 | 是否启用 | 是 |

### 集成步骤

1. **获取飞书API凭证**
   - 在飞书开放平台创建应用
   - 获取 App ID 和 App Secret
   - 申请多维表格读取权限

2. **修改 `fetchAdsFromFeishu` 函数**

> ⚠️ **安全警告**: 以下代码仅为示例。实际生产环境中，**绝不要在前端代码中硬编码API凭证**！请通过后端服务器代理API调用，将敏感凭证保存在服务器端。

**推荐架构**: 前端 → 后端API → 飞书API

```javascript
// 推荐方式：通过后端代理
async function fetchAdsFromFeishu() {
  try {
    // 调用自己的后端API，而不是直接调用飞书API
    const response = await fetch('/api/ads');
    const ads = await response.json();
    return ads;
  } catch (error) {
    console.error('Failed to fetch ads:', error);
    return [adConfig.currentAd];
  }
}
```

**仅供开发测试的示例代码** (生产环境禁用):

```javascript
async function fetchAdsFromFeishu() {
  try {
    // 1. 获取访问令牌
    const tokenResponse = await fetch('https://open.feishu.cn/open-apis/auth/v3/tenant_access_token/internal', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        app_id: 'YOUR_APP_ID',
        app_secret: 'YOUR_APP_SECRET'
      })
    });
    const { tenant_access_token } = await tokenResponse.json();

    // 2. 获取表格数据
    const tableResponse = await fetch('https://open.feishu.cn/open-apis/bitable/v1/apps/YOUR_APP_TOKEN/tables/YOUR_TABLE_ID/records', {
      headers: {
        'Authorization': `Bearer ${tenant_access_token}`,
        'Content-Type': 'application/json'
      }
    });
    const data = await tableResponse.json();

    // 3. 处理数据
    return processFeishuData(data);
  } catch (error) {
    console.error('Failed to fetch ads from Feishu:', error);
    return [adConfig.currentAd]; // 降级返回默认配置
  }
}
```

3. **实现 `processFeishuData` 函数**

```javascript
function processFeishuData(data) {
  if (!data.data || !data.data.items) return [];
  
  return data.data.items
    .filter(item => item.fields.enabled) // 只返回启用的广告
    .map(item => ({
      id: item.fields.id,
      type: item.fields.type,
      priority: item.fields.priority,
      url: item.fields.url,
      title: item.fields.title || '',
      description: item.fields.description || '',
      imageUrl: item.fields.imageUrl || '',
      startDate: item.fields.startDate ? new Date(item.fields.startDate) : null,
      endDate: item.fields.endDate ? new Date(item.fields.endDate) : null
    }));
}
```

4. **更新初始化逻辑**

```javascript
async function initAdSystem() {
  if (!adSection) return;
  
  // 从飞书API获取广告数据
  const ads = await fetchAdsFromFeishu();
  
  // 筛选可展示的广告（按优先级排序）
  const visibleAds = ads
    .filter(ad => shouldShowAd(ad))
    .sort((a, b) => {
      const priorityOrder = { high: 3, medium: 2, low: 1 };
      return (priorityOrder[b.priority] || 0) - (priorityOrder[a.priority] || 0);
    });
  
  // 展示第一个符合条件的广告
  if (visibleAds.length > 0) {
    renderAd(visibleAds[0]);
    adSection.classList.add('is-visible');
  }
  
  // ... 其余代码保持不变
}
```

## 测试建议 (Testing Recommendations)

1. **日期测试**: 修改系统时间到2026年2月27日后，验证广告是否自动隐藏
2. **类型测试**: 分别测试三种广告类型的展示效果
3. **优先级测试**: 测试不同优先级的视觉差异
4. **关闭功能**: 测试关闭按钮和sessionStorage持久化
5. **响应式测试**: 在不同屏幕尺寸下测试布局

## 安全考虑 (Security Considerations)

1. **iframe沙箱**: iframe已配置sandbox属性，限制潜在的安全风险。移除了`allow-same-origin`以防止恶意脚本访问父页面
2. **HTTPS**: 确保所有外部资源使用HTTPS协议
3. **凭证安全**: 
   - ⚠️ **重要**: 不要在前端代码中硬编码API密钥
   - 建议通过后端代理处理所有API调用
   - 使用环境变量存储敏感配置
   - 定期轮换API密钥
4. **内容审核**: 定期审核广告内容，确保符合平台规范
5. **CSP策略**: 考虑配置Content Security Policy限制可加载的资源来源

## 维护建议 (Maintenance)

- 定期检查广告链接的有效性
- 监控广告加载性能
- 收集用户反馈，优化展示策略
- 保持飞书API版本更新

## 更新日志 (Changelog)

### 2026-02-11
- 初始版本发布
- 支持iframe类型广告展示gift.coludai.cn
- 实现日期自动隐藏（2026-02-27到期）
- 添加用户关闭功能
- 预留飞书API集成接口
