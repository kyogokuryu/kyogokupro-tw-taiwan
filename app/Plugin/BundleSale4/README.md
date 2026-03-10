# BundleSale4
EC-CUBE4対応のセット商品販売プラグイン

## 納品書にセット商品の内訳を表示する方法

本体のOrderPdfService.phpの490行目付近に以下のコードを追記

```
if($OrderItem->getProduct()->getBundleItems()) {
  foreach($OrderItem->getProduct()->getBundleItems() as $bundleItem) {
    $productName .= PHP_EOL.$bundleItem->getProductClass()->formattedProductName();
  }
}
```

## 管理画面の受注編集にセット商品の内訳を表示する方法

app/template/admin/Order/edit.twigを設置  
779行目付近に以下のコードを追記


```
{% for BundleItem in OrderItem.Product.BundleItems %}
  <p>{{ BundleItem.ProductClass.formattedProductName }}</p>
{% endfor %}
```
