# SR思锐 团队网站 / SR Studio Website

## 简介 (中文)
SR思锐 团队官网的静态站点，展示团队的青少年编程教育项目、社区入口、隐私政策与使用条款。页面采用无框架的 HTML/CSS/JavaScript，可直接部署在任意静态托管平台。

## 主要特性
- 首页含英雄区、产品卡片、社区入口、团队介绍与联系方式
- **🆕 动态广告系统**：通过飞书多维表格 API 获取赞助商广告（详见 `AD_SYSTEM_README.md`）
- 无依赖构建流程，直接打开 `index.html` 即可浏览
- 响应式布局与辅助功能（导航折叠、滚动样式、跳转到内容）
- 第三方静态资源：Font Awesome、Google Fonts CDN
- 隐私政策与使用条款独立页面，便于合规展示

## 仓库结构
- `index.html`：主站页面
- `styles.css`：全站样式
- `script.js`：导航折叠、滚动状态、年份更新、广告系统等交互
- `privacy.html` / `service.html`：隐私政策与使用条款
- `api/`：**广告 API 后端服务器**（Node.js + Express）
  - `server.js`：飞书 API 集成服务
  - `package.json`：依赖配置
  - `README.md`：API 文档
- `images/`：站点配图与图标
- `sounds/`：音频资源（如 `rbc.mp3`）
- `AD_SYSTEM_README.md`：广告系统详细文档
- `IMPLEMENTATION.md`：飞书集成实现说明
- 其他静态资源：`404.html` 等

## 本地预览
```powershell
# 克隆仓库并进入项目目录（将 <repo-url> 替换为实际地址）
git clone <repo-url>
cd SRInternet-Studio

# 方式 1：直接双击 index.html 用浏览器打开（广告系统将使用备用数据）
# 方式 2：启动本地静态服务（示例使用 Python）
python -m http.server 8080
# 然后访问 http://localhost:8080

# 方式 3：完整体验（包括广告 API）
# 启动后端 API 服务器
cd api
npm install
npm start
# 在另一个终端启动前端
cd ..
python -m http.server 8080
```

## 部署建议
- GitHub Pages：将仓库设为公开，Pages 指向主分支根目录即可。
- 其他静态托管（Vercel/Netlify/Nginx 等）：将仓库根目录作为静态资源根路径部署。

## 社区与支持
- Bilibili：<https://space.bilibili.com/1969160969>
- GitHub：<https://github.com/SRInternet-Studio>
- QQ 交流群：<https://qm.qq.com/cgi-bin/qm/qr?k=0OC7vApC79hlsj1cx1SapeOKI_PaAaXY&jump_from=webapi&authKey=4c9uHeinCJS+DhdSe/CRUVCL6h22wqKtzrTxO82E1QSh4mwB9B5e3liZKOl1G8kN>
- 邮箱：admin@sr-studio.cn

## 许可
- 代码遵循 MIT 许可证，详见 `service.html` 中的相关说明。
- 站点中的图片、音频及其他原创内容版权归 SR思锐 团队或原权利人所有，未经授权请勿商用。

---

## Overview (English)
A static website for the SR Studio (SR思锐 团队) showcasing youth-focused coding projects, community links, privacy policy, and terms of use. Pure HTML/CSS/JS with no build step; ready for any static host.

## Features
- Hero, product highlights, community links, about, and contact sections
- **🆕 Dynamic Ad System**: Fetches sponsor ads from Feishu multi-dimensional table API (see `AD_SYSTEM_README.md`)
- Zero-build workflow; open `index.html` directly
- Responsive layout with accessible navigation toggle and skip link
- External assets via Font Awesome and Google Fonts CDNs
- Dedicated privacy (`privacy.html`) and terms (`service.html`) pages for compliance

## Repository Structure
- `index.html`: main landing page
- `styles.css`: global styling
- `script.js`: nav toggle, scroll state, and footer year update
- `privacy.html` / `service.html`: privacy policy and terms of use
- `images/`: site imagery and icons
- `sounds/`: audio assets (e.g., `rbc.mp3`)
- Other static assets: `404.html`, etc.

## Local Preview
```powershell
# Clone and enter the project (replace <repo-url> with the actual URL)
git clone <repo-url>
cd SRInternet-Studio

# Option 1: open index.html in your browser directly
# Option 2: serve locally (example with Python)
python -m http.server 8080
# Visit http://localhost:8080
```

## Deployment
- GitHub Pages: point Pages to the repository root of the default branch.
- Any static host (Vercel/Netlify/Nginx/etc.): deploy the repo root as static assets.

## Community & Support
- Bilibili: <https://space.bilibili.com/1969160969>
- GitHub: <https://github.com/SRInternet-Studio>
- QQ Group: <https://qm.qq.com/cgi-bin/qm/qr?k=0OC7vApC79hlsj1cx1SapeOKI_PaAaXY&jump_from=webapi&authKey=4c9uHeinCJS+DhdSe/CRUVCL6h22wqKtzrTxO82E1QSh4mwB9B5e3liZKOl1G8kN>
- Email: admin@sr-studio.cn

## License
- Code is MIT-licensed (see notes in `service.html`).
- Images, audio, and other original content remain the property of SR Studio or respective owners; commercial use requires permission.
