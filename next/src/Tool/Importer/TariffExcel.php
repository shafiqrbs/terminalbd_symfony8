<?php

namespace App\Tool\Importer;

use Doctrine\Persistence\ManagerRegistry;
use App\Tool\Entity\TaxTariff;

class TariffExcel
{
    protected ?ManagerRegistry $doctrine = null;

    #[\Symfony\Contracts\Service\Attribute\Required]
    public function setDoctrine(ManagerRegistry $doctrine): void
    {
        $this->doctrine = $doctrine;
    }


    private $data = [];

    public function import($data)
    {
        $this->data = $data;

        foreach($this->data as $key => $item) {

            $hsCode = $item['HSCode'];
            $productOld = $this->getDoctrain()->getRepository('SettingToolBundle:TaxTariff')->findOneBy(['hsCode' => $hsCode]);
            if(empty($productOld) and !empty($item['Name'])){
                $product = new TaxTariff();
                $product->setHsCode($item['HSCode']);
                $product->setName( $item['Name']);
                $product->setCustomsDuty( $item['CD']);
                $product->setSupplementaryDuty( $item['SD']);
                $product->setValueAddedTax( $item['VAT']);
                $product->setAdvanceIncomeTax( $item['AIT']);
                $product->setRegularityDuty( $item['RD']);
                $product->setAdvanceTax( $item['ATV']);
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