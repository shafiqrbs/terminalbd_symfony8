<?php

namespace App\Inventory\Importer;

use App\Inventory\Entity\ItemBrand;
use Doctrine\Bundle\DoctrineBundle\Registry;
use App\Inventory\Repository\ItemBrandRepository;
use App\Inventory\Repository\ItemSizeRepository;
use App\Inventory\Entity\Item;
use App\Inventory\Entity\ItemSize;
use App\Inventory\Entity\Product;
use App\Product\Entity\Category;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;


class ProductExcel
{
    use ContainerAwareTrait;

    /* @var $excelImport  ExcelImporter */
    protected $excelImport;


    private $data = [];

    public function isValid($data) {

        return true;

    }

    public function import($data)
    {
        $this->data = $data;
        $inventory = $this->excelImport->getInventoryConfig();
        foreach($this->data as $key => $item) {

            $name = ucfirst(strtolower($item['ProductName']));
            $category = ucfirst(strtolower($item['Category']));
            $productID = $item['ProductID'];
            $productOld = $this->getDoctrain()->getRepository(\App\Inventory\Entity\Item::class)->findOneBy(['inventoryConfig' => $inventory,'name' => $name]);
            if(empty($productOld)){
                $salesPrice = empty($item['SalesPrice']) ? 0 : $item['SalesPrice'];
                $purchasePrice = empty($item['PurchasePrice']) ? 0 : $item['PurchasePrice'];
                $unit = empty($item['Unit']) ? 'Pcs' : $item['Unit'];
                $barcode = empty($item['Barcode']) ? $productID  : time();
                $model = empty($item['model']) ? time() : $item['model'];
                $product = new Item();
                $product->setInventoryConfig($inventory);
                $product->setName($name);
                $product->setSalesPrice($salesPrice);
                $product->setPurchasePrice($purchasePrice);
                $product->setBarcode($barcode);
                $product->setModel($model);
                if ($name) {
                    $master = $this->getMasterItem($name,$category,$unit);
                    $product->setMasterItem($master);
                }
                $brand = $item['Brand'];
                if ($brand) {
                    $brand = $this->getBrand(ucfirst(strtolower($brand)));
                    $product->setBrand($brand);
                }
                $size = $item['Size'];
                if ($size) {
                    $size = $this->getSize(ucfirst(strtolower($size)));
                    $product->setSize($size);
                }
                $this->save($product);
            }

        }

    }

    private function getMasterItem($item,$category,$unit)
    {
        $inventory = $this->excelImport->getInventoryConfig();
        $masterRepository = $this->getProductRepository();

        $product = $masterRepository->findOneBy([
            'inventoryConfig'   => $inventory,
            'name'              => $item
        ]);
        if($product){
            return $product;
        }else{
            $product = new Product();
            $product->setName($item);
            $product->setInventoryConfig($inventory);
            $unit = $this->getDoctrain()->getRepository(\App\Tool\Entity\ProductUnit::class)->findOneBy(['name' => $unit]);
            if(!empty($unit)){
                $product->setProductUnit($unit);
            }
            if ($category) {
                $category = $this->getCategory(ucfirst(strtolower($category)));
                $product->setCategory($category);
            }
            $product = $this->save($product);
            return $product;
        }
    }

    private function getCategory($item)
    {
        $config = $this->excelImport->getInventoryConfig();
        $global = $this->excelImport->getInventoryConfig()->getGlobalOption();
        $categoryRepository = $this->getCategoryRepository();
        $category = $categoryRepository->findOneBy(
            [
                'globalOption'   => $global,
                'name' => $item,
            ]
        );
        if($category){
            return $category;
        }else{
            $category = new Category();
            $category->setName($item);
            $category->setGlobalOption($global);
            $category->setinventoryConfig($config);
            $category->setPermission('private');
            $category->setStatus(true);
            $category = $this->save($category);
            return $category;
        }


    }

    private function getBrand($item)
    {

        $config = $this->excelImport->getInventoryConfig();
        $brandRepository = $this->getBrandRepository();

        $brand = $brandRepository->findOneBy([
            'inventoryConfig'   => $config,
            'name'              => $item
        ]);
        if($brand){
            return $brand;
        }else{
            $brand = new ItemBrand();
            $brand->setName($item);
            $brand->setinventoryConfig($config); $brand->setinventoryConfig($config);
            $brand = $this->save($brand);
            return $brand;
        }

    }

    private function getSize($item)
    {
        $config = $this->excelImport->getInventoryConfig();
        $sizeRepository = $this->getSizeRepository();

        $size = $sizeRepository->findOneBy([
            'inventoryConfig'   => $config,
            'name'              => $item
        ]);

        if($size){
            return $size;
        }else{
            $size = new ItemSize();
            $size->setinventoryConfig($config);
            $size->setName($item);
            $size = $this->save($size);
            return $size;
        }

    }

    private function save($entity){
        $this->persist($entity);
        $this->getEntityManager()->flush();
        return $entity;
    }

    public function setExcelImport($excelImport)
    {
        $this->excelImport = $excelImport;
    }


    /**
     * @return \Doctrine\Common\Persistence\ObjectManager|object
     */
    private function getEntityManager()
    {
        return $this->getDoctrain()->getManager();
    }


    private function persist($entity){
        $this->getEntityManager()->persist($entity);
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
        return $this->container->get('doctrine');
    }

    /**
     * @return  @return \App\Inventory\Repository\ProductRepository
     */
    private function getProductRepository()
    {
        return $this->getDoctrain()->getRepository(\App\Inventory\Entity\Product::class);
    }

    /**
     * @return ItemBrandRepository
     */
    private function getBrandRepository()
    {
        return $this->getDoctrain()->getRepository(\App\Inventory\Entity\ItemBrand::class);
    }
    

    /**
     * @return ItemSizeRepository
     */
    private function getSizeRepository()
    {
        return $this->getDoctrain()->getRepository(ItemSize::class);
    }

    /**
     * @return  @return \Product\Bundle\ProductProductBundle\Entity\CategoryRepository
     */
    private function getCategoryRepository()
    {
        return $this->getDoctrain()->getRepository(\App\Product\Entity\Category::class);
    }


}