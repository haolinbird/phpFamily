# jumei 环境初始化组件
## 版本 0.1.0-beta
* 支持从http header中解析环境变量
```
// 请在项目的最开始添加.
// 如果存在MNLogger RPC_SR/HTTP_SR/HTTP_SERVICE_SR调用, 应确保该初始化在MNLogger调用之前.
\JmArchiTracker\Tracker::init();
```
