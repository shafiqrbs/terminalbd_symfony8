<?php

namespace App\Hospital\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Hospital\Entity\Particular;
use App\Hospital\Entity\HmsCommission;
use App\Tool\Entity\TransactionMethod;
use App\Accounting\Entity\AccountBank;
use App\Accounting\Entity\AccountMobileBank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Hospital\Entity\DoctorInvoice;
use Doctrine\ORM\EntityRepository;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class DoctorInvoiceType extends AbstractType
{
    protected $globalOption;


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->globalOption = $options['globalOption'];

        $builder

            ->add('paymentMobile',TextType::class, ['attr'=>['class'=>'m-wrap span12 mobile','placeholder'=>'Add payment mobile no','data-original-title'=>'Add payment mobile no','autocomplete'=>'off']])
            ->add('transactionId',TextType::class, ['attr'=>['class'=>'m-wrap span12 numeric','placeholder'=>'Add mobile transaction id','data-original-title'=>'Add mobile transaction id','autocomplete'=>'off']])
            ->add('payment',TextType::class, ['attr'=>['class'=>'m-wrap span12 tooltips payment','data-trigger' => 'hover','placeholder'=>'Payment amount','data-original-title'=>'Enter payment amount','autocomplete'=>'off'],
                'constraints' =>[
                    new NotBlank(message: 'Payment input required'),
                ]
            ])
            ->add('comment',TextareaType::class, ['attr'=>['class'=>'m-wrap span12','rows'=>3,'placeholder'=>'Add remarks','autocomplete'=>'off']])
            ->add('assignDoctor', EntityType::class, [
                  'required'    => true,
                  'choice_label' => 'referred',
                    'constraints' =>[
                        new NotBlank(message: 'Please select doctor/referred/agent'),
                    ],
                  'placeholder' => '--- Select Doctor/Referred ---',
                  'attr'=>['class'=>'m-wrap span12 select2'],
                  'class' => Particular::class,
                  'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                      ->where('e.hospitalConfig ='.$this->globalOption->getHospitalConfig()->getId())
                      ->andWhere('e.service IN (:service)')
                      ->setParameter('service', [5,6])
                      ->orderBy("e.name","ASC")

            ])
            ->add('hmsCommission', EntityType::class, [
                  'required'    => true,
                  'choice_label' => 'name',
                  'placeholder' => '--- Select commission type ---',
                  'constraints' =>[
                        new NotBlank(message: 'Payment input required'),
                  ],
                  'attr'=>['class'=>'m-wrap span12'],
                  'class' => HmsCommission::class,
                  'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                      ->where('e.hospitalConfig ='.$this->globalOption->getHospitalConfig()->getId())
                      ->andWhere("e.status = 1")
                      ->orderBy("e.name","ASC")

            ])
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
            ->add('accountBank', EntityType::class, [
                'required'    => false,
                'class' => AccountBank::class,
                'choice_label' => 'name',
                'attr'=>['class'=>'span12 select2'],
                'placeholder' => '---Choose receive bank account---',
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('b')
                    ->where("b.status = 1")
                    ->andWhere("b.globalOption =".$this->globalOption->getId())
                    ->orderBy("b.name", "ASC")
            ])
            ->add('file')
            ->add('accountMobileBank', EntityType::class, [
                'required'    => false,
                'class' => AccountMobileBank::class,
                'choice_label' => 'name',
                'attr'=>['class'=>'span12 select2'],
                'placeholder' => '---Choose receive mobile bank account---',
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('b')
                    ->where("b.status = 1")
                    ->andWhere("b.globalOption =".$this->globalOption->getId())
                    ->orderBy("b.name", "ASC")
            ])
        ;

    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'globalOption' => null,
        ]);

        $resolver->setDefaults([
            'data_class' => DoctorInvoice::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'appstore_bundle_hospitalbundle_doctor_invoice';
    }
}
