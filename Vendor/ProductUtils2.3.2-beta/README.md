ProductUtils
====

## 安装 ProductUtils

```bash
php composer.phar require ProductUtils
```


## 提供的接口

以下是该工具提供的功能，工具内的一些参数由 `Dove` key 配置, 请为其添加`Dove` 并保证命名空间一致.

#### \ProductUtils\Util::getSpuImageUrlBySize($spu_id, $width, $order = 0)
使用前需要在项目目录里配置 \Config\Config::$spu_image ,用来加载 图片配置信息。
```php
public $sku_image = "#{operation-service.sku_image_business}";
```

#### \ProductUtils\Util::getSkuImageUrlBySize($sku_no, $width, $order = 0)
使用前需要在项目目录里配置 \Config\Config::$sku_image ,用来加载 图片配置信息。

```php
public $spu_image = "#{operation-service.spu_image_business}";
```


#### \ProductUtils\Util::getProductImageUrlBySize($productId, $width)
使用前需要在项目目录里配置 \Config\Config::$productImageConfig ,用来加载 图片配置信息。

```php
public $productImageConfig = "#{product-service.productImageUrlConfig";
```

## 更新记录

* 2018.12.14,2.3.1, 增加 \ProductUtils\ImageBackgroundColorDetector 工具，用于检测图片是否是白色背景（需要启用gd扩展）；

* 2018.11.23,2.2.7-beta,增加\ProductUtils\Util::getCouTuanRedEnvelopeMaxDeductAmount方法，用于获取凑团商品最大红包抵扣金额。

* 2018.11.24,2.2.7
 增加\ProductUtils\Util::isCouTuanRedEnvelopeAbUser方法，判断给定的用户是否进行ab。
