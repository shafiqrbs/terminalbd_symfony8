<?php

namespace App\Hospital\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use App\DomainUser\Form\CustomerForHospitalType;
use App\DomainUser\Form\CustomerType;
use App\Hospital\Entity\Category;
use App\Hospital\Entity\HmsCategory;
use App\Hospital\Repository\CategoryRepository;
use App\Hospital\Repository\HmsCategoryRepository;
use Doctrine\ORM\EntityRepository;
use App\Location\Repository\LocationRepository;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class InvoicePatientType extends AbstractType
{
    protected $globalOption;


    /** @var  LocationRepository */
    private $location;

    /** @var  HmsCategoryRepository */
    private $emCategory;

    /** @var  GlobalOption */

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->globalOption = $options['globalOption'];
        $this->emCategory = $options['emCategory'];
        $this->location = $options['location'];

        $builder

            ->add('cardNo', TextType::class, array('attr'=>array('class'=>'m-wrap span12','placeholder'=>'Add payment card no','data-original-title'=>'Add payment card no','autocomplete'=>'off')))
            ->add('transactionId', TextType::class, array('attr'=>array('class'=>'m-wrap span12','placeholder'=>'Add payment transaction id','data-original-title'=>'Add payment transaction id','autocomplete'=>'off')))
            ->add('paymentMobile', TextType::class, array('attr'=>array('class'=>'m-wrap span12 mobile','placeholder'=>'Add payment mobile no','data-original-title'=>'Add payment mobile no','autocomplete'=>'off')))
            ->add('deliveryDateTime', TextType::class, array('attr'=>array('class'=>'m-wrap span10 tooltips','data-trigger' => 'hover','placeholder'=>'Report delivery date','data-original-title'=>'Report delivery date','autocomplete'=>'off'),
                'constraints' =>array(
                    new NotBlank(message: 'Add delivery date time'),
                )
            ))
            ->add('payment', TextType::class, array('attr'=>array('class'=>'tooltips','data-trigger' => 'hover','placeholder'=>'Receive amount','data-original-title'=>'Enter received amount','autocomplete'=>'off'),
                'constraints' =>array(
                    new NotBlank(message: 'Add receive amount'),
                )
            ))
            ->add('comment', TextType::class, array('attr'=>array('class'=>'m-wrap span12','placeholder'=>'Add remarks','autocomplete'=>'off')))
            ->add('referredDoctor', EntityType::class, array(
                  'required'    => true,
                  'choice_label' => 'referred',
                  'placeholder' => '--- Select Referred Doctor/Agent ---',
                  'attr'=>array('class'=>'m-wrap span12 select2'),
                  'class' => 'App\Hospital\Entity\Particular',
                  'query_builder' => function(EntityRepository $er){
                      return $er->createQueryBuilder('e')
                          ->where("e.service = 6")
                          ->orderBy("e.particularCode","ASC");
                  }

            ))

            ->add('transactionMethod', EntityType::class, array(
                'required'    => true,
                'class' => 'App\Tool\Entity\TransactionMethod',
                'choice_label' => 'name',
                'attr'=>array('class'=>'span12 m-wrap transactionMethod'),
                'query_builder' => function(EntityRepository $er){
                    return $er->createQueryBuilder('e')
                        ->where("e.status = 1")
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
        ;
        $builder->add('referredDoctor', InvoiceReferredDoctorType::class);
        $builder->add('customer', CustomerForHospitalType::class);
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'globalOption' => null,
            'emCategory' => null,
            'location' => null,
        ]);

        $resolver->setDefaults(array(
            'data_class' => 'App\Hospital\Entity\Invoice'
        ));
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'appstore_bundle_hospitalbundle_invoice';
    }

    /**
     * @return mixed
     */
    protected function PathologyChoiceList()
    {
        return $this->emCategory->getParentCategoryTree($parent = 2 /** Pathology */ );

    }

    /**
     * @return mixed
     */
    protected function DepartmentChoiceList()
    {
        return $this->emCategory->getParentCategoryTree($parent = 7 /** Department */);

    }

}
