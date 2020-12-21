# 该组件封装了一些常用的微信公众号/小程序需要使用的API

# 开始使用
## 需要使用到Log, Redis组件, 确保这些组件相关的配置已经可用.
## 拷贝Weixin.schema.php到项目的Config目录, 配置上对应的小程序或公众号的appid,appsecret,token等相关信息, 其中redis需要用来来缓存access_token, 填写Config/Redis.php中的使用配置名称即可.

# 调用方式
### 小程序 : \Weixin\Applet::instance('foo')->getAccesstoken(), 获取foo这个小程序全局唯一后台接口调用凭据
### 公众号 : \Weixin\H5::instance('bar')->getAccesstoken(), 获取bar这个公众号全局唯一后台接口调用凭据