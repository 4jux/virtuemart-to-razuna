# virtuemart-to-razuna
Movin pictures from virtuemart to razuna and adding labels.

The projeckt is on going test for simplify virtuemart move over to magento.

Desided to keep magento pictures seperatly in the Razuna DB.

So magento searches pictures from Razuna DB by the product SKU. 

Script imports pictures from virtuemart and adds them to Razuna and adds SKU(in razuna "label") to picture.

**To get needed data from virtuemart mysql**

First make sql recuest

```
SELECT
    product_sku, product_thumb_image, product_full_image
FROM
    jos_vm_product
```

If using phpmyadmin then export the request to csv file.