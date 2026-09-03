<?php

namespace App\Tool\Importer;
use App\Medicine\Repository\MedicineCompanyRepository;
use App\Medicine\Repository\MedicineGenericRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Medicine\Entity\MedicineBrand;
use App\Medicine\Entity\MedicineCompany;
use App\Medicine\Entity\MedicineGeneric;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;


class Excel
{
    use ContainerAwareTrait;

    private $data = [];
    private $cache = [];

	public function isValid($data) {

		return true;

	}

    public function import($data)
    {
        $this->data = $data;

        foreach($this->data as $key => $item) {

            $name = $item['BrandName'];
            $strength  = $item['Strength'];
            $dosage = $item['Dosage'];
            $name = $this->getDoctrain()->getRepository(\App\Medicine\Entity\MedicineBrand::class)->findOneBy(['name' => $name,'strength' => $strength,'medicineForm' => $dosage]);
            if(empty($name) and !empty($item['BrandName']) ) {
                $medicine = new MedicineBrand();
                $medicine->setMedicineCompany( $this->getMedicineCompany( $item ) );
                $medicine->setMedicineGeneric( $this->getMedicineGeneric( $item ) );
                $medicine->setName( $item['BrandName'] );
                $medicine->setStrength( $item['Strength'] );
                $medicine->setMedicineForm( $item['Dosage'] );
                $medicine->setUseFor( $item['UseFor'] );
                $medicine->setDar( $item['DAR'] );
                $this->persist( $medicine );
                $this->flush();
            }

        }
    }

    public function importOld($data)
    {
        $this->data = $data;

        foreach($this->data as $key => $item) {

          	$name = $item['BrandName'];
	        $strength  = $item['Strength'];
	        $dosage = $item['Dosage'];
            $name = $this->getDoctrain()->getRepository(\App\Medicine\Entity\MedicineBrand::class)->findOneBy(['name' => $name,'strength' => $strength,'medicineForm' => $dosage]);
	        if(empty($name) and !empty($item['BrandName']) ) {
		        $medicine = new MedicineBrand();
		        $medicine->setMedicineCompany( $this->getMedicineCompany( $item ) );
		        $medicine->setMedicineGeneric( $this->getMedicineGeneric( $item ) );
		        $medicine->setName( $item['BrandName'] );
		        $medicine->setStrength( $item['Strength'] );
		        $medicine->setMedicineForm( $item['Dosage'] );
		        $medicine->setUseFor( $item['UseFor'] );
		        $medicine->setDar( $item['DAR'] );
		        $this->persist( $medicine );
		        $this->flush();
	        }

        }
    }


	private function getMedicineCompany($item)
	{
		$company = $this->getCachedData('Company', $item['Company']);
		$companyRepository = $this->getMedicineCompanyRepository();
		if($company == NULL) {
			$company = $companyRepository->findOneBy([
				'name'                => $item['Company']
			]);
			if($company == NULL) {
				$company = new MedicineCompany();
				$company->setName($item['Company']);
				$company = $this->save($company);
			}
			$this->setCachedData('Company', $item['Company'], $company);
		}

		return $company;
	}

	private function getMedicineGeneric($item)
	{
		$generic = $this->getCachedData('GenericName', $item['GenericName']);
		$genericRepository = $this->getMedicineGenericRepository();
		if($generic == NULL) {

			$generic = $genericRepository->findOneBy([
				'name'                => $item['GenericName']
			]);
			if($generic == null) {
				$generic = new MedicineGeneric();
				$generic->setName($item['GenericName']);
				$generic = $this->save($generic);
			}
			$this->setCachedData('GenericName', $item['GenericName'], $generic);
		}

		return $generic;
	}

    /**
     * @return \Doctrine\Common\Persistence\ObjectManager|object
     */
    private function getEntityManager()
    {
        return $this->getDoctrain()->getManager();
    }

    /**
     * @return MedicineCompanyRepository
     */
    private function getMedicineCompanyRepository()
    {
        return $this->getDoctrain()->getRepository(\App\Medicine\Entity\MedicineCompany::class);
    }

    /**
     * @return MedicineGenericRepository
     */
    private function getMedicineGenericRepository()
    {
        return $this->getDoctrain()->getRepository(\App\Medicine\Entity\MedicineGeneric::class);
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