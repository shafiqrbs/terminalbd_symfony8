<?php

namespace App\Inventory\Importer;

use Exception;
use DateTime;
use App\Inventory\Repository\ItemRepository;
use App\Inventory\Repository\PurchaseVendorItemRepository;
use App\Inventory\Repository\ProductRepository;
use App\Inventory\Repository\ItemColorRepository;
use App\Inventory\Repository\ItemSizeRepository;
use App\Inventory\Repository\VendorRepository;
use App\Tool\Repository\ProductUnitRepository;
use App\Inventory\Repository\PurchaseRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Inventory\Entity\Item;
use App\Inventory\Entity\ItemBrand;
use App\Inventory\Entity\ItemColor;
use App\Inventory\Entity\ItemSize;
use App\Inventory\Entity\Purchase;
use App\Inventory\Entity\PurchaseItem;
use App\Inventory\Entity\PurchaseVendorItem;
use App\Inventory\Entity\StockItem;
use App\Inventory\Entity\Vendor;
use App\Product\Entity\Category;
use App\Tool\Entity\ProductUnit;
use App\Inventory\Entity\Product;

class Excel
{
    protected ?ManagerRegistry $doctrine = null;

    #[\Symfony\Contracts\Service\Attribute\Required]
    public function setDoctrine(ManagerRegistry $doctrine): void
    {
        $this->doctrine = $doctrine;
    }


    protected $inventoryConfig;
    private $data = [];
    private $cache = [];

    public function isValid($data) {

        if($this->inventoryConfig == null) {
            throw new Exception("You must set config first");
        }

        $vendorRepository = $this->getVendorRepository();
        $purchaseRepository = $this->getPurchaseRepository();

        foreach ($data as $item) {

            if(empty($item['Memo']) || empty($item['Vendor'])) {
                return false;
            }

            $vendor = $this->getCachedData('Vendor', $item['Vendor']);
            if($vendor == null) {
                $vendor = $vendorRepository->findOneBy([
                    'inventoryConfig'   => $this->getInventoryConfig(),
                    'name'              => $item['Vendor']
                ]);
                if($vendor != null) {
                    $this->setCachedData('Vendor', $item['Vendor'], $vendor);
                }
            }


            if($vendor == null) {
                continue;
            }

            $key = $item['Vendor'] . "_" . $item['Memo'] ;

            $purchase = $this->getCachedData('Purchase', $key);

            if($purchase == NULL) {

                $purchase = $purchaseRepository->findOneBy([
                    'vendor'=> $vendor,
                    'memo'=> $item['Memo'],
                    'inventoryConfig'=> $this->getInventoryConfig()
                ]);

                if($purchase != NULL) {
                    $this->setCachedData('Purchase', $key, $purchase);
                }
            }

            if($purchase != null) {
                  return false;
            }
        }

        return true;

    }

    public function import($data)
    {
        $this->data = $data;

        foreach($this->data as $key => $item) {

            $item = $this->senatizeItemData($key, $item, 'Default');

            $purchaseItem = new PurchaseItem();
            $purchaseItem->setItem($this->getItem($item));
            $purchaseItem->setPurchase($this->getPurchase($item));
            $purchaseItem->setQuantity($item['Quantity']);
            $purchaseItem->setPurchasePrice($item['PurchasePrice']);
            $purchaseItem->setPurchaseSubTotal((float)$item['PurchasePrice'] * (int)$item['Quantity']);
            $purchaseItem->setSalesPrice($item['SalesPrice']);
            $purchaseItem->setSalesSubTotal((float)$item['SalesPrice'] * (int)$item['Quantity']);
            $purchaseItem->setPurchaseVendorItem($this->getPurchaseVendorItem($item));
            $this->persist($purchaseItem);

            $stockItem = new StockItem();
            $stockItem->setInventoryConfig($this->getInventoryConfig());
            $stockItem->setPurchaseItem($purchaseItem);
            $stockItem->setItem($purchaseItem->getItem());
            $stockItem->setQuantity($purchaseItem->getQuantity());
            $stockItem->setCreatedBy($purchaseItem->getPurchase()->getCreatedBy());
            $stockItem->setProduct($purchaseItem->getItem()->getMasterItem());
            $stockItem->setProductName($purchaseItem->getItem()->getMasterItem()->getName());
            $stockItem->setCategory($purchaseItem->getItem()->getMasterItem()->getCategory());
            $stockItem->setVendor($purchaseItem->getPurchase()->getVendor());
            $stockItem->setVendorName($purchaseItem->getPurchase()->getVendor()->getName());
            if(!empty($purchaseItem->getItem()->getMasterItem()->getCategory())){
                $stockItem->setCategory($purchaseItem->getItem()->getMasterItem()->getCategory());
                $stockItem->setCategoryName($purchaseItem->getItem()->getMasterItem()->getCategory()->getName());
            }

            if(!empty($purchaseItem->getItem()->getMasterItem()->getProductUnit())) {
                $stockItem->setUnit($purchaseItem->getItem()->getMasterItem()->getProductUnit());
                $stockItem->setUnitName($purchaseItem->getItem()->getMasterItem()->getProductUnit()->getName());
            }

            if(!empty($purchaseItem->getPurchaseVendorItem()->getBrand())) {
                $stockItem->setBrand($purchaseItem->getPurchaseVendorItem()->getBrand());
                $stockItem->setBrandName($purchaseItem->getPurchaseVendorItem()->getBrand()->getName());
            }


            if(!empty($purchaseItem->getItem()->getSize())){
                $stockItem->setSize($purchaseItem->getItem()->getSize());
                $stockItem->setSizeName($purchaseItem->getItem()->getSize()->getName());
            }

            if(!empty($purchaseItem->getItem()->getColor())){
                $stockItem->setColor($purchaseItem->getItem()->getColor());
                $stockItem->setColorName($purchaseItem->getItem()->getColor()->getName());
            }
            $stockItem->setProcess('purchase');

            $this->persist($stockItem);
            $this->flush();

        }
    }

    public function setInventoryConfig($inventoryConfig)
    {
        $this->inventoryConfig = $inventoryConfig;
    }

    private function getItem($item)
    {
        $key = $item['ProductName'] . "_" . $item['Color'] . "_" . $item['Size'] . "_" . $item['Vendor'] . "_" . $item['Category'];

        $itemObj = $this->getCachedData('Item', $key);

        if($itemObj == NULL) {

            $masterItem = $this->getMasterItem($item);
            $itemSize = $this->getSize($item);
            $itemColor = $this->getColor($item);
            $vendor = $this->getVendor($item);
            $brand = $this->getBrand($item);
            $itemObj = $this->checkFindItem($item);
            if($itemObj == NULL) {
                $itemObj = new Item();
                $itemObj->setName($this->sentence_case($item['ProductName']));
                $itemObj->setMasterItem($masterItem);
                $itemObj->setCategory($masterItem->getCategory());
                if (trim($item['Barcode'])){
                    $itemObj->setBarcode(trim($item['Barcode']));
                }else{
                    $itemObj->setBarcode($itemObj->getSku());
                }
                if($this->getInventoryConfig()->getIsColor() == 1) {
                    $itemObj->setColor($itemColor);
                }
                if($this->getInventoryConfig()->getIsSize() == 1) {
                    $itemObj->setSize($itemSize);
                }
                if($this->getInventoryConfig()->getIsVendor() == 1) {
                    $itemObj->setVendor($vendor);
                }
                if($this->getInventoryConfig()->getIsBrand() == 1) {
                    $itemObj->setBrand($brand);
                }
                if($this->getInventoryConfig()->isModel() == 1) {
                    $itemObj->setModel($item['Model']);
                }
                $itemObj->setInventoryConfig($this->getInventoryConfig());
                $itemObj = $this->save($itemObj);
            }
            $this->setCachedData('Item', $key, $itemObj);
        }

        return $itemObj;
    }

    private function checkFindItem($item)
    {
        $repository = $this->getItemRepository();

        $masterItem = $this->getMasterItem($item);

        $qb = $repository->createQueryBuilder('i');
        $qb->where('i.inventoryConfig ='.$this->getInventoryConfig()->getId());
        $qb->andWhere('i.masterItem ='.$masterItem->getId());

        if($this->getInventoryConfig()->getIsSize()) {
            $itemSize = $this->getSize($item);
            $qb->andWhere('i.size =' . $itemSize->getId());
        }

        if($this->getInventoryConfig()->getIsColor()){
            $itemColor = $this->getColor($item);
            $qb->andWhere('i.color ='.$itemColor->getId());
        }

        if($this->getInventoryConfig()->getIsVendor()) {
            $vendor = $this->getVendor($item);
            $qb->andWhere('i.vendor ='.$vendor->getId());
        }

        if($this->getInventoryConfig()->getIsBrand()){
            $brand = $this->getBrand($item);
            $qb->andWhere('i.brand ='.$brand->getId());
        }

        if($this->getInventoryConfig()->isModel()){
            $model = $item['model'];
            $qb->andWhere("i.model ='{$model}'");
        }
        return $qb->getQuery()->getOneOrNullResult();

    }

    private function getPurchaseVendorItem($item)
        {
            $key = $item['ProductName'] . "_" . $item['Vendor'] . "_" . $item['PurchasePrice']. "_" . $item['Memo'] ;

            $itemObj = $this->getCachedData('PurchaseVendorItem', $key);
            $brand = $this->getBrand($item);
            $repository = $this->getPurchaseVendorItemRepository();


            if ($itemObj == NULL) {

                $purchase = $this->getPurchase($item);
                $itemObj = $repository->findOneBy([
                    'name' => $this->sentence_case($item['ProductName']),
                    'purchasePrice' => $item['PurchasePrice'],
                    'purchase' => $purchase
                ]);

                if ($itemObj == NULL) {

                    $itemObj = new PurchaseVendorItem();
                    $masterItem = $this->getMasterItem($item);
                    $itemObj->setMasterItem($masterItem);
                    $itemObj->setBrand($brand);
                    $itemObj->setName($this->sentence_case($item['ProductName']));
                    $itemObj->setWebName($this->sentence_case($item['ProductName']));
                    $itemObj->setPurchase($purchase);
                    $itemObj->setSource('inventory');
                    $itemObj->setInventoryConfig($purchase->getInventoryConfig());
                    $itemObj->setPurchasePrice($item['PurchasePrice']);
                    $itemObj->setSalesPrice($item['SalesPrice']);
                    $itemObj->setWebPrice($item['SalesPrice']);
                    $itemObj->setQuantity((int)$item['Quantity']);
                    $itemObj = $this->save($itemObj);

                } else {
                    $itemObj->setQuantity($itemObj->getQuantity() + (int)$item['Quantity']);
                }

                $this->setCachedData('PurchaseVendorItem', $key, $itemObj);

            } else {

                $itemObj->setQuantity($itemObj->getQuantity() + (int)$item['Quantity']);
                $this->setCachedData('PurchaseVendorItem', $key, $itemObj);
            }

            return $itemObj;
        }

    private function getMasterItem($item)
    {
        $key = $item['ProductName'] . "_" . $item['Category'];
        $masterItem = $this->getCachedData('MasterItem', $key);
        $category = $this->getCategory($item);
        $unit = $this->getUnit($item);
        $masterItemRepository = $this->getMasterItemRepository();

        if($masterItem == NULL) {
            $masterItem = $masterItemRepository->findOneBy([
                'name' => $this->sentence_case($item['ProductName']),
                'inventoryConfig' => $this->getInventoryConfig()
            ]);

            if($masterItem == NULL){
                $masterItem = new Product();
                $masterItem->setName($this->sentence_case($item['ProductName']));
                $masterItem->setCategory($category);
                $masterItem->setProductUnit($unit);
                $masterItem->setInventoryConfig($this->getInventoryConfig());
                $masterItem = $this->save($masterItem);
            }
            $this->setCachedData('MasterItem', $key, $masterItem);
        }

        return $masterItem;
    }

    private function getColor($item)
    {
        $color = $this->getCachedData('Color', $item['Color']);
        $colorRepository = $this->getColorRepository();
        if($color == NULL) {
            $color = $colorRepository->findOneBy([
                'name'                => $item['Color']
            ]);
            if($color == NULL) {
                $color = new ItemColor();
                $color->setName($item['Color']);
                $color = $this->save($color);
            }
            $this->setCachedData('Color', $item['Color'], $color);
        }

        return $color;
    }

    private function getSize($item)
    {
        $size = $this->getCachedData('Size', $item['Size']);
        $sizeRepository = $this->getSizeRepository();
        if($size == NULL) {

            $size = $sizeRepository->findOneBy([
                'inventoryConfig'   => $this->getInventoryConfig(),
                'name'                => $item['Size']
            ]);

            if($size == null) {
                $size = new ItemSize();
                $size->setName($item['Size']);
                $size->setInventoryConfig($this->getInventoryConfig());
                $size = $this->save($size);

            }
            $this->setCachedData('Size', $item['Size'], $size);
        }

        return $size;
    }

    private function getUnit($item)
    {
        $unit = $this->getCachedData('Unit', $item['Unit']);
        $unitRepository = $this->getUnitRepository();
        if($unit == NULL) {
            $unit = $unitRepository->findOneBy([
                'name'  => $item['Unit']
            ]);

            if($unit == null) {
                $unit = new ProductUnit();
                $unit->setName($item['Unit']);
                $unit = $this->save($unit);
            }
            $this->setCachedData('Unit', $item['Unit'], $unit);
        }

        return $unit;
    }


    private function getPurchase($item)
    {
        $key = $item['Vendor'] . "_" . $item['Memo'] ;

        $purchase = $this->getCachedData('Purchase', $key);

        $purchaseRepository = $this->getPurchaseRepository();

        if($purchase == NULL) {
            $vendor = $this->getVendor($item);
            $purchase = $purchaseRepository->findOneBy([
                'vendor'=> $vendor,
                'memo'=> $item['Memo'],
                'inventoryConfig'=> $this->getInventoryConfig()
            ]);

            if($purchase == NULL) {
                $purchase = new Purchase();
                $purchase->setInventoryConfig($this->getInventoryConfig());
                $purchase->setVendor($vendor);
                $purchase->setChalan(1);
                $purchase->setProcess('imported');
                $purchase->setReceiveDate(new DateTime());
                $purchase->setMemo($item['Memo']);
                $purchase = $this->save($purchase);
            }

            $this->setCachedData('Purchase', $key, $purchase);
        }

        return $purchase;
    }


    private function getCategory($item)
    {
        $category = $this->getCachedData('Category', $item['Category']);
        $categoryRepository = $this->getCategoryRepository();
        if($category == NULL) {
            $category = $categoryRepository->findOneBy(
                [
                    'inventoryConfig'   => $this->getInventoryConfig(),
                    'name' => $item['Category'],
                    'permission' => 'private'
                ]
            );
            $this->setCachedData('Category', $item['Category'], $category);
            if($category == null) {
                $category = new Category();
                $category->setName($item['Category']);
                $category->setInventoryConfig($this->getInventoryConfig());
                $category->setPermission('private');
                $category->setStatus(true);
                $category = $this->save($category);
            }
            $this->setCachedData('Category',  $item['Category'] , $category);
        }

        return $category;

    }

    private function getBrand($item)
    {
        $brand = $this->getCachedData('Brand', $item['Brand']);

        $brandRepository = $this->getBrandRepository();

        if($brand == NULL) {

            $brand = $brandRepository->findOneBy([
                'inventoryConfig'   => $this->getInventoryConfig(),
                'name'              => $item['Brand']
            ]);

            if($brand == null) {
                $brand = new ItemBrand();
                $brand->setName($item['Brand']);
                $brand->setBrandCode($item['BrandCode']);
                $brand->setInventoryConfig($this->getInventoryConfig());
                $brand = $this->save($brand);
            }

            $this->setCachedData('Brand', $item['Brand'], $brand);
        }

        return $brand;
    }

    private function getVendor($item)
    {
        $vendor = $this->getCachedData('Vendor', $item['Vendor']);

        $vendorRepository = $this->getVendorRepository();

        if($vendor == NULL) {
            $vendor = $vendorRepository->findOneBy([
                'inventoryConfig'   => $this->getInventoryConfig(),
                'name'              => $item['Vendor']
            ]);
            if($vendor == NULL)  {
                $vendor = new Vendor();
                $vendor->setName($item['Vendor']);
                $vendor->setCompanyName($item['Vendor']);
                $vendor->setVendorCode($item['VendorCode']);
                $vendor->setInventoryConfig($this->getInventoryConfig());
                $vendor = $this->save($vendor);
            }

            $this->setCachedData('Vendor', $item['Vendor'], $vendor);
        }

        return $vendor;
    }


    /**
     * @return \Doctrine\Common\Persistence\ObjectManager|object
     */
    private function getEntityManager()
    {
        return $this->getDoctrain()->getManager();
    }

    /**
     * @return ItemRepository
     */
    private function getItemRepository()
    {
        return $this->getDoctrain()->getRepository(\App\Inventory\Entity\Item::class);
    }

    /**
     * @return PurchaseVendorItemRepository
     */
    private function getPurchaseVendorItemRepository()
    {
        return $this->getDoctrain()->getRepository(\App\Inventory\Entity\PurchaseVendorItem::class);
    }

    /**
     * @return ProductRepository
     */
    private function getMasterItemRepository()
    {
        return $this->getDoctrain()->getRepository(\App\Inventory\Entity\Product::class);
    }

    private function save($entity){
        $this->persist($entity);
        $this->getEntityManager()->flush();
        return $entity;
    }

    private function persist($entity){
        $this->getEntityManager()->persist($entity);
    }

    private function getCachedData($type, $key)
    {
        return $this->cache[$type][$key] ?? NULL;
    }

    private function setCachedData($type, $key, $value)
    {
        $this->cache[$type][$key] = $value;
    }

    /**
     * @return ItemColorRepository
     */
    private function getColorRepository()
    {
        return $this->getDoctrain()->getRepository(ItemColor::class);
    }

    /**
     * @return ItemSizeRepository
     */
    private function getSizeRepository()
    {
        return $this->getDoctrain()->getRepository(ItemSize::class);
    }

    /**
     * @return VendorRepository
     */
    private function getVendorRepository()
    {
        return $this->getDoctrain()->getRepository(\App\Inventory\Entity\Vendor::class);
    }

    /**
     * @return  @return \App\Inventory\Repository\ItemBrandRepository
     */
    private function getBrandRepository()
    {
        return $this->getDoctrain()->getRepository(\App\Inventory\Entity\ItemBrand::class);
    }

    /**
     * @return  @return \App\Product\Entity\CategoryRepository
     */
    private function getCategoryRepository()
    {
        return $this->getDoctrain()->getRepository(\App\Product\Entity\Category::class);
    }

    /**
     * @return ProductUnitRepository
     */
    private function getUnitRepository()
    {
        return $this->getDoctrain()->getRepository(\App\Tool\Entity\ProductUnit::class);
    }


    private function getInventoryConfig()
    {
        $inventoryConfig = $this->getCachedData('InventoryConfig', $this->inventoryConfig);

        if($inventoryConfig == NULL) {
            $inventoryConfig = $this->getDoctrain()->getRepository(\App\Inventory\Entity\InventoryConfig::class)->find($this->inventoryConfig);
            $this->setCachedData('InventoryConfig', $this->inventoryConfig, $inventoryConfig);
        }

        return $inventoryConfig;
    }

    /**
     * @return PurchaseRepository
     */
    private function getPurchaseRepository()
    {
        $purchaseRepository = $this->getDoctrain()->getRepository(\App\Inventory\Entity\Purchase::class);

        return $purchaseRepository;
    }

    private function flush()
    {
        $this->getEntityManager()->flush();
    }

    /**
     * @return Registry
     */
    private function getDoctrain()
    {
        return $this->doctrine;
    }

    /**
     * @param $key
     * @param $item
     * @param $defaultStr
     *
     * @return mixed
     */
    private function senatizeItemData($key, $item, $defaultStr)
    {
        if (empty($item['Size'])) {
            $item['Size'] = $defaultStr;
        }else{
            $item['Size'] = $item['Size'] . "";
        }

        if (empty($item['Color'])) {
            $item['Color'] = $defaultStr;
        }

        if (empty($item['Vendor'])) {
            $item['Vendor'] = $defaultStr;
        }

        if (empty($item['Brand'])) {
            $item['Brand'] = $defaultStr;
        }

        if (empty($item['Category'])) {
            $item['Category'] = $defaultStr;
        }


        if (empty($item['Quantity'])) {
            $item['Quantity'] = 1;
        }

        if (empty($item['Unit'])) {
            $item['Unit'] = $defaultStr;
        }


        $this->data[$key] = $item;

        return $item;
    }

    public function sentence_case($string) {
        $sentences = preg_split('/([.?!]+)/', $string, -1, PREG_SPLIT_NO_EMPTY|PREG_SPLIT_DELIM_CAPTURE);
        $new_string = '';
        foreach ($sentences as $key => $sentence) {
            $new_string .= ($key & 1) == 0?
                ucfirst(strtolower(trim($sentence))) :
                $sentence.' ';
        }
        return trim($new_string);
    }


}