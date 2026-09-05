<?php

namespace App\Tool\Importer;

use Doctrine\Persistence\ManagerRegistry;
use App\Inventory\Entity\Product;


class ProductExcel
{
    protected ?ManagerRegistry $doctrine = null;

    #[\Symfony\Contracts\Service\Attribute\Required]
    public function setDoctrine(ManagerRegistry $doctrine): void
    {
        $this->doctrine = $doctrine;
    }


    protected $productImport;

    private $data = [];

    public function import($data)
    {
        $this->data = $data;

        foreach($this->data as $key => $item) {

            $inventory = $this->productImport->getInventoryConfig();

            $productName = $item['ProductName'];
            $productOld = $this->getDoctrain()->getRepository(\App\Inventory\Entity\Product::class)->findOneBy(['inventoryConfig' => $inventory , 'name' => $productName]);

            $category = $item['Category'];
            if(!empty($category)){
                $category = $this->getDoctrain()->getRepository(\App\Product\Entity\Category::class)->findOneBy(['name' => $productName]);
            }

            $unit = $item['Unit'];
            if($unit){
                $unit = $this->getDoctrain()->getRepository(\App\Inventory\Entity\Product::class)->findOneBy(['name' => $unit]);
            }

            if(empty($productOld)){
                $product = new Product();
                $product->setInventoryConfig($inventory);
                if(!empty($category)){
                    $product->setCategory($category);
                }
                if(!empty($unit)){
                    $product->setProductUnit($unit);
                }
                $this->persist($product);
                $this->flush();
            }

        }

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
        return $this->doctrine;
    }


}