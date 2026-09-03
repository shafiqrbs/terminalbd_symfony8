<?php

namespace App\DoctorPrescription\Form;

use App\DomainUser\Form\CustomerForDmsType;
use App\DomainUser\Form\CustomerForDpsType;
use App\DomainUser\Form\CustomerForHospitalType;
use App\DomainUser\Form\CustomerType;
use App\Hospital\Entity\Category;
use App\Hospital\Entity\HmsCategory;
use App\Hospital\Repository\CategoryRepository;
use App\Hospital\Repository\HmsCategoryRepository;
use App\Medicine\Repository\DiagnosticReportRepository;
use Doctrine\ORM\EntityRepository;
use App\Location\Repository\LocationRepository;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class PrescriptionType extends AbstractType
{

    /** @var  LocationRepository */
    private $location;

    /** @var  DiagnosticReportRepository */
    private $diagnostic;

    /** @var  GlobalOption */
    private $globalOption;


    function __construct(GlobalOption $globalOption ,  LocationRepository $location ,  DiagnosticReportRepository $diagnostic)
    {
        $this->location         = $location;
        $this->diagnostic         = $diagnostic;
        $this->globalOption     = $globalOption;
    }


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            ->add('process', 'choice', array(
                'attr'=>array('class'=>'m-wrap invoiceProcess inputs select-custom'),
                'expanded'      =>false,
                'multiple'      =>false,
                'empty_value' => '---Choose process---',
                'choices' => array(
                    'In-progress' => 'In-progress',
                    'Closed' => 'Closed',
                ),
            ))
            ->add('hmsDiseasesProfile', 'entity', array(
                'required'    => false,
                'class' => 'App\Hospital\Entity\HmsServiceGroup',
                'property' => 'name',
                'empty_value' => '---Select diseases profile---',
                'attr'=>array('class'=>'span12 m-wrap invoiceProcess select2'),
                'constraints' =>array(
                    new NotBlank(array('message'=>'Select diseases profile')),
                ),
                'query_builder' => function(EntityRepository $er){
                    return $er->createQueryBuilder('e')
                        ->where('e.hospitalConfig ='.$this->globalOption->getHospitalConfig()->getId())
                        ->andWhere("e.service = 11")
                        ->andWhere("e.status = 1")
                        ->orderBy("e.name","ASC");
                }
            ));
            if ($this->globalOption->getDpsConfig()->isInvestigation() == 1){

                $builder->add('investigations', 'entity', array(
                    'required' => false,
                    'multiple' => true,
                    'attr' => array('class' => 'm-wrap span12 select2'),
                    'class' => 'App\Medicine\Entity\DiagnosticReport',
                    'group_by' => 'category.name',
                    'property' => 'name',
                    'choice_translation_domain' => true,
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('e')
                            ->join("e.category", 'c')
                            ->orderBy("e.name", "ASC");
                    }

                ));
            }
           // TODO(migration): SF3+ takes a form type class-string, not an instance.
           // Move these constructor arguments into CustomerForDpsType::configureOptions() and pass them as form options: $this->location 
           $builder->add('customer', new CustomerForDpsType( $this->location ));
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'App\DoctorPrescription\Entity\DpsInvoice'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'appstore_bundle_dpsinvoice';
    }

    /**
     * @return mixed
     */
    protected function categoryChoiceList()
    {
        return $categoryTree = $this->diagnostic->getInvestigationGroupList();
    }

}
