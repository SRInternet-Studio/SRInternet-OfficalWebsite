# SR-Admin 使用说明

## 功能概览

- 使用 PHP + SQLite 管理官网内容
- 支持导航栏、Hero、产品、社区、成员、联系我们、管理员、操作日志
- 产品图片严格校验后上传到 `static/images/products`
- 使用会话登录、密码哈希、CSRF 校验、登录失败限流、PDO 预处理语句

## 安装与登录

- 安装地址：`/SR-Admin/install.php`
- 登录地址：`/SR-Admin/login.php`
- 安装时需要手动创建首个管理员账号、邮箱和密码
- 安装完成后，安装程序会自动锁定

## 运行要求

- PHP 8.1 或更高版本
- 建议开启 `pdo_sqlite`、`sqlite3`、`fileinfo` 扩展
- Web 服务器需要能够执行 `.php`

## 数据文件

- SQLite 数据库文件会在安装时随机生成，位于 `SR-Admin/data/`
- 安装程序会把随机数据库文件名写入 `SR-Admin/data/runtime.php`
- 前台接口 `/api/site-content.php` 与后台会自动读取该运行时配置

## 前台联动

- 官网通过 `/api/site-content.php` 读取数据库内容
- `index.html` 中的导航、Hero、产品、社区、成员、联系信息会在页面加载后自动同步

## 上传限制

- 仅允许 `jpg`、`png`、`webp`
- 单张图片最大 3MB
- 会校验 MIME、图片尺寸与真实图片格式
