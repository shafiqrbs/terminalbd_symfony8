<?php

namespace App\DoctorPrescription\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
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

class InvoiceTransactionType extends AbstractType
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
                'attr'=>array('class'=>'m-wrap invoiceProcess select-custom'),
                'expanded'      =>false,
                'multiple'      =>false,
                'placeholder' => '---Choose process---',
                'choices' => array('Created' => 'Created', 'Appointment' => 'Appointment', 'Visit' => 'Visit', 'Done' => 'Done', 'Canceled' => 'Canceled'),
            ))

            ->add('investigations', EntityType::class, array(
                'required'    => true,
                'multiple'      =>true,
                'attr'=>array('class'=>'m-wrap span12 select2'),
                'class' => 'App\Medicine\Entity\DiagnosticReport',
                'group_by'  => 'category.name',
                'choice_label'  => 'name',
                'choice_translation_domain' => true,
                'query_builder' => function(EntityRepository $er){
                    return $er->createQueryBuilder('e')
                        ->join("e.category",'c')
                        ->orderBy("e.name", "ASC");
                }

            ))

            ->add('cardNo', TextType::class, array('attr'=>array('class'=>'m-wrap span12','placeholder'=>'Add payment card no','data-original-title'=>'Add payment card no','autocomplete'=>'off')))
            ->add('transactionId', TextType::class, array('attr'=>array('class'=>'m-wrap span12','placeholder'=>'Add payment transaction id','data-original-title'=>'Add payment transaction id','autocomplete'=>'off')))
            ->add('paymentMobile', TextType::class, array('attr'=>array('class'=>'m-wrap span12 mobile','placeholder'=>'Add payment mobile no','data-original-title'=>'Add payment mobile no','autocomplete'=>'off')))
            ->add('transactionMethod', EntityType::class, array(
                'required'    => true,
                'class' => 'App\Tool\Entity\TransactionMethod',
                'choice_label' => 'name',
                'attr'=>array('class'=>'span12 m-wrap transactionMethod'),
                'query_builder' => function(EntityRepository $er){
                    return $er->createQueryBuilder('e')
                        ->where("e.status = 1")
                        ->andWhere("e.slug != 'cash-on-delivery'")
                        ->orderBy("e.id","ASC");
                }
            ))
            ->add('paymentCard', EntityType::class, array(
                'required'    => false,
                'choice_label' => 'name',
                'class' => 'App\Tool\Entity\PaymentCard',
                'attr'=>array('class'=>'span12 m-wrap'),
                'placeholder' => '---Choose payment card---',
                'query_builder' => function(EntityRepository $er){
                    return $er->createQueryBuilder('e')
                        ->where("e.status = 1")
                        ->orderBy("e.id","ASC");
                }
            ))

            ->add('accountBank', EntityType::class, array(
                'required'    => false,
                'class' => 'App\Accounting\Entity\AccountBank',
                'choice_label' => 'name',
                'attr'=>array('class'=>'span12 select2'),
                'placeholder' => '---Choose receive bank account---',
                'query_builder' => function(EntityRepository $er){
                    return $er->createQueryBuilder('b')
                        ->where("b.status = 1")
                        ->andWhere("b.globalOption =".$this->globalOption->getId())
                        ->orderBy("b.name", "ASC");
                }
            ))

            ->add('accountMobileBank', EntityType::class, array(
                'required'    => false,
                'class' => 'App\Accounting\Entity\AccountMobileBank',
                'choice_label' => 'name',
                'attr'=>array('class'=>'span12 select2'),
                'placeholder' => '---Choose receive mobile bank account---',
                'query_builder' => function(EntityRepository $er){
                    return $er->createQueryBuilder('b')
                        ->where("b.status = 1")
                        ->andWhere("b.globalOption =".$this->globalOption->getId())
                        ->orderBy("b.name", "ASC");
                }
            ))
            ->add('assignDoctor', EntityType::class, array(
                'required'    => true,
                'class' => 'App\DoctorPrescription\Entity\DpsParticular',
                'choice_label' => 'name',
                'multiple'    => false,
                'expanded' => false,
                'attr'=>array('class'=>'m-wrap span12'),
                'query_builder' => function(EntityRepository $er){
                    return $er->createQueryBuilder('e')
                        ->join("e.service",'s')
                        ->where("e.status = 1")
                        ->andWhere('e.dpsConfig =:dpsConfig')
                        ->setParameter('dpsConfig', $this->globalOption->getDpsConfig()->getId())
                        ->andWhere('s.slug IN (:slugs)')
                        ->setParameter('slugs',array('doctor'))
                        ->orderBy("e.name","ASC");
                }
            ));
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
