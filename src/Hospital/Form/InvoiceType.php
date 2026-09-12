<?php

namespace App\Hospital\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Tool\Entity\TransactionMethod;
use App\Tool\Entity\PaymentCard;
use App\Accounting\Entity\AccountBank;
use App\Accounting\Entity\AccountMobileBank;
use App\Hospital\Entity\Particular;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Hospital\Entity\Invoice;
use App\DomainUser\Form\CustomerForHospitalType;
use App\Hospital\Entity\HospitalConfig;
use App\Hospital\Repository\HmsCategoryRepository;
use Doctrine\ORM\EntityRepository;
use App\Location\Repository\LocationRepository;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;

class InvoiceType extends AbstractType
{
    protected $globalOption;
    protected $emCategory;
    protected $location;


    /** @var  HospitalConfig */
    private $config;

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

            ->add('cardNo',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Add payment card no','data-original-title'=>'Add payment card no','autocomplete'=>'off']])
            ->add('transactionId',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Add payment transaction id','data-original-title'=>'Add payment transaction id','autocomplete'=>'off']])
            ->add('paymentMobile',TextType::class, ['attr'=>['class'=>'m-wrap span12 mobile','placeholder'=>'Add payment mobile no','data-original-title'=>'Add payment mobile no','autocomplete'=>'off']])
            ->add('payment',NumberType::class, ['attr'=>['class'=>'tooltips payment numeric span11 input2 m-wrap','data-trigger' => 'hover','placeholder'=>'Receive','data-original-title'=>'Enter valid receive amount, if receive amount is due input zero','autocomplete'=>'off'],
            ])
            ->add('discountCalculation',NumberType::class, ['attr'=>['class'=>'tooltips initialDiscount span11 input2 m-wrap','data-trigger' => 'hover','placeholder'=>'Discount','data-original-title'=>'Enter valid discount amount','autocomplete'=>'off'],
            ])
            ->add('consultant',TextType::class, ['attr'=>['class'=>'consultant select2 span12 m-wrap','placeholder'=>'Search consultant name','autocomplete'=>'off'],
            'required'=> false,'mapped'=> false
            ])

            ->add('referredId',TextType::class, ['attr'=>['class'=>'referred select2 span12 m-wrap','placeholder'=>'Search referred name','autocomplete'=>'off'],
            'required'=> false,'mapped'=> false
            ])

            ->add('isHold',CheckboxType::class, [
                'attr'=>['class'=>'tooltips custom-control-input','data-trigger' => 'hover','placeholder'=>'Receive']
                ,'required'=> false,'mapped'=> false
            ])
            ->add('isDiscount',CheckboxType::class, [
                'attr'=>['class'=>'tooltips custom-control-input isDiscount','data-trigger' => 'hover','placeholder'=>'Receive']
                ,'required'=> false,'mapped'=> false
            ])
            ->add('discount',HiddenType::class,['attr'=>['class'=>'discount']])
            ->add('isDiscount',CheckboxType::class, [
                'attr'=>['class'=>'tooltips custom-control-input','data-trigger' => 'hover','placeholder'=>'Receive']
            ,'required'=> false,'mapped'=> false
            ])
            ->add('discountRequestedBy',TextType::class, ['attr'=>['class'=>'m-wrap span12 requested2User','placeholder'=>'Discount requested by','autocomplete'=>'off']])
            ->add('discountRequestedComment',TextareaType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Discount requested comment','autocomplete'=>'off']])
            ->add('comment',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Add remarks','autocomplete'=>'off']])
            ->add('transactionMethod', EntityType::class, [
                'required'    => true,
                'class' => TransactionMethod::class,
                'choice_label' => 'name',
                'attr'=>['class'=>'span12 m-wrap transactionMethod'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->andWhere("e.slug != 'cash-on-delivery'")
                    ->orderBy("e.id","ASC")
            ])
            ->add('paymentCard', EntityType::class, [
                'required'    => false,
                'choice_label' => 'name',
                'class' => PaymentCard::class,
                'attr'=>['class'=>'span12 m-wrap'],
                'placeholder' => '---Choose payment card---',
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->orderBy("e.id","ASC")
            ])

            ->add('accountBank', EntityType::class, [
                'required'    => false,
                'class' => AccountBank::class,
                'choice_label' => 'name',
                'attr'=>['class'=>'span12 m-wrap'],
                'placeholder' => '---Choose receive bank account---',
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('b')
                    ->where("b.status = 1")
                    ->andWhere("b.globalOption =".$this->globalOption->getId())
                    ->orderBy("b.name", "ASC")
            ])
            ->add('accountMobileBank', EntityType::class, [
                'required'    => false,
                'class' => AccountMobileBank::class,
                'choice_label' => 'name',
                'attr'=>['class'=>'span12 m-wrap'],
                'placeholder' => '---Choose receive mobile bank account---',
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('b')
                    ->where("b.status = 1")
                    ->andWhere("b.globalOption =".$this->globalOption->getId())
                    ->orderBy("b.name", "ASC")
            ])

        ;
        if( $this->config->isMarketingExecutive() == 1){

            $builder->add('marketingExecutive', EntityType::class, [
                'required'    => false,
                'class' => Particular::class,
                'choice_label' => 'marketingExecutiveEmployee',
                'attr'=>['class'=>'span12 select2 m-wrap marketingExecutive'],
                'placeholder' => '--- Choose Marketing Executive ---',
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('b')
                    ->where("b.status = 1")
                    ->andWhere('b.service IN(:service)') ->setParameter('service',array_values([14]))
                    ->andWhere("b.hospitalConfig =".$this->globalOption->getHospitalConfig()->getId())
                    ->orderBy("b.name", "ASC")
            ]);
        }
        if( $this->globalOption->getHospitalConfig()->isAdvanceSearchParticular() == 1 ){
            $builder->add('particulars',TextType::class, [
                'attr'=>['class'=>'particulars select2 span12 m-wrap','placeholder'=>'Test Name, Accessories, Surgery etc.','autocomplete'=>'off']
            ,'required'=> false,'mapped'=> false
            ]);
        }
        $builder->add('referredDoctor', InvoiceReferredDoctorType::class,['mapped'=> false]);
        $builder->add('assignDoctor', InvoiceDoctorType::class,['mapped'=> false]);
        $builder->add('customer', CustomerForHospitalType::class);

    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'globalOption' => null,
            'emCategory' => null,
            'location' => null,
        ]);

        $resolver->setDefaults([
            'data_class' => Invoice::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'appstore_bundle_hospitalbundle_invoice';
    }

}
