<?php

namespace App\DoctorPrescription\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
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
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class PrescriptionType extends AbstractType
{
    protected $globalOption;


    /** @var  LocationRepository */
    private $location;

    /** @var  DiagnosticReportRepository */
    private $diagnostic;

    /** @var  GlobalOption */

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->globalOption = $options['globalOption'];
        $this->location = $options['location'];
        $this->diagnostic = $options['diagnostic'];

        $builder

            ->add('process', ChoiceType::class, array(
                'attr'=>array('class'=>'m-wrap invoiceProcess inputs select-custom'),
                'expanded'      =>false,
                'multiple'      =>false,
                'placeholder' => '---Choose process---',
                'choices' => array('In-progress' => 'In-progress', 'Closed' => 'Closed'),
            ))
            ->add('hmsDiseasesProfile', EntityType::class, array(
                'required'    => false,
                'class' => 'App\Hospital\Entity\HmsServiceGroup',
                'choice_label' => 'name',
                'placeholder' => '---Select diseases profile---',
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

                $builder->add('investigations', EntityType::class, array(
                    'required' => false,
                    'multiple' => true,
                    'attr' => array('class' => 'm-wrap span12 select2'),
                    'class' => 'App\Medicine\Entity\DiagnosticReport',
                    'group_by' => 'category.name',
                    'choice_label' => 'name',
                    'choice_translation_domain' => true,
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('e')
                            ->join("e.category", 'c')
                            ->orderBy("e.name", "ASC");
                    }

                ));
            }
           $builder->add('customer', CustomerForDpsType::class, ['location' => $this->location]);
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'globalOption' => null,
            'location' => null,
            'diagnostic' => null,
        ]);

        $resolver->setDefaults(array(
            'data_class' => 'App\DoctorPrescription\Entity\DpsInvoice'
        ));
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
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
