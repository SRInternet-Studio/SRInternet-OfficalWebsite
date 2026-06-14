# SR思锐 团队官网

## 简介

这是 SR思锐 团队官网源码，包含首页展示、社区入口、团队信息、法律页面以及赞助商广告系统。

前端仍为原生 HTML/CSS/JavaScript，赞助商广告接口现已改为 PHP 实现。

## 主要特性

- 首页展示团队产品、社区与联系信息
- 底部赞助商广告从飞书多维表格动态读取
- 广告接口位于 `api/ads.php`
- 无前端构建流程，静态资源可直接部署
- 页面保留响应式布局和基础无障碍支持

## 目录结构

- `index.html`：主站页面
- `static/css/`：样式文件
- `static/js/main.js`：页面交互和广告系统逻辑
- `api/`：PHP 广告接口
- `images/`：图片资源
- `privacy.html` / `service.html`：法律页面
- `AD_SYSTEM_README.md`：广告系统说明
- `test-ads.html`：广告接口与渲染测试页

## 本地预览

### 只看静态页面

直接打开 `index.html` 即可，此时广告系统会使用前端备用广告。

### 测试完整广告接口

需要使用支持 PHP 的本地环境，例如 Apache、Nginx + PHP-FPM、宝塔或任意 PHP 开发环境。

配置步骤：

```bash
cd api
cp .env.example .env
```

然后填写飞书配置，并通过你的 PHP Web 服务访问站点根目录。

## 广告接口

- 广告列表：`/api/ads.php`
- 单条广告：`/api/ads.php?id=<ad-id>`
- 健康检查：`/api/health.php`

详细说明见 `api/README.md` 与 `AD_SYSTEM_README.md`。

## 部署建议

- 纯静态托管可展示页面，但广告接口不会工作
- 需要动态广告时，请部署到支持 PHP 的服务器
- 建议站点和 `api/*.php` 同域部署，避免额外跨域配置

## 社区与支持

- Bilibili：<https://space.bilibili.com/1969160969>
- GitHub：<https://github.com/SRInternet-Studio>
- QQ 交流群：<https://qm.qq.com/cgi-bin/qm/qr?k=0OC7vApC79hlsj1cx1SapeOKI_PaAaXY&jump_from=webapi&authKey=4c9uHeinCJS+DhdSe/CRUVCL6h22wqKtzrTxO82E1QSh4mwB9B5e3liZKOl1G8kN>
- 邮箱：admin@sr-studio.cn

## 许可

- 代码遵循 MIT 许可证
- 图片与其他原创内容版权归原作者或 SR思锐 团队所有
