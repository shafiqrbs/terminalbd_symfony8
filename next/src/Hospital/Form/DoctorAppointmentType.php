<?php

namespace App\Hospital\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Hospital\Entity\HmsServiceGroup;
use App\Hospital\Entity\Particular;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use App\Tool\Entity\TransactionMethod;
use App\Tool\Entity\PaymentCard;
use App\Accounting\Entity\AccountBank;
use App\Accounting\Entity\AccountMobileBank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Hospital\Entity\Invoice;
use App\DomainUser\Form\PatientForDoctorAppointmentType;
use App\Hospital\Repository\HmsCategoryRepository;
use Doctrine\ORM\EntityRepository;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;

class DoctorAppointmentType extends AbstractType
{


    public function __construct(private readonly GlobalOption $globalOption, private readonly HmsCategoryRepository $emCategory)
    {
    }


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            ->add('cardNo',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Add payment card no','data-original-title'=>'Add payment card no','autocomplete'=>'off']])
            ->add('followUpId',TextType::class, [ 'required'=> false,'attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter Follow-up ID','autocomplete'=>'off']])
            ->add('transactionId',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Add payment transaction id','data-original-title'=>'Add payment transaction id','autocomplete'=>'off']])
            ->add('paymentMobile',TextType::class, ['attr'=>['class'=>'m-wrap span12 mobile','placeholder'=>'Add payment mobile no','data-original-title'=>'Add payment mobile no','autocomplete'=>'off']])
            ->add('payment',NumberType::class, ['attr'=>['class'=>'tooltips payment numeric number span11 input2 m-wrap','data-trigger' => 'hover','placeholder'=>'Receive','data-original-title'=>'Enter valid receive amount, if receive amount is due input zero','autocomplete'=>'off'],
            ])
            ->add('smsAlert',CheckboxType::class, ['attr'=> ['class'=>'custom-control-input']])
            ->add('isConfirm',CheckboxType::class, ['attr'=> ['class'=>'m-wrap custom-control-input'],'mapped' => false,'data' => false])
            ->add('appointmentDate', DateType::class, [
                'widget' => 'single_text',
                'html5' => true,
                'required' => true,
                'attr' => ['class' => 'm-wrap span12', 'min' => date('Y-m-d'),'placeholder'=>"Expected Date",'data-trigger' => "focus"],
            ])

            ->add('visitType', EntityType::class, [
                'required'    => true,
                'class' => HmsServiceGroup::class,
                'property' => 'name',
                'attr'=>['class'=>'span12 m-wrap visitType'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where('e.hospitalConfig ='.$this->globalOption->getHospitalConfig()->getId())
                    ->andWhere("e.service = 13")
                    ->andWhere("e.status = 1")
                    ->orderBy("e.name","ASC")
            ])
            ->add('assignDoctor', EntityType::class, [
                'required'    => true,
                'class' => Particular::class,
                'property' => 'visitDoctor',
                'empty_value' => '---Select Assign Doctor---',
                'attr'=>['class'=>'span12 m-wrap select2 assignDoctor'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where('e.hospitalConfig ='.$this->globalOption->getHospitalConfig()->getId())
                    ->andWhere("e.service = 5")
                    ->andWhere("e.status = 1")
                    ->orderBy("e.name","ASC")
            ])
            ->add('referredDoctor', EntityType::class, [
                'required'    => false,
                'class' => Particular::class,
                'property' => 'referredName',
                'attr'=>['class'=>'span12 select2 m-wrap referredDoctor'],
                'empty_value' => '--- Choose Referred ---',
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('b')
                    ->where("b.status = 1")
                    ->andWhere('b.service IN(:service)') ->setParameter('service',array_values([5,6]))
                    ->andWhere("b.hospitalConfig =".$this->globalOption->getHospitalConfig()->getId())
                    ->orderBy("b.name", "ASC")
            ])

            ->add('diseasesProfile', EntityType::class, [
                'required'    => false,
                'class' => HmsServiceGroup::class,
                'property' => 'name',
                'empty_value' => '---Select diseases profile---',
                'attr'=>['class'=>'span12 m-wrap select2 diseasesProfile'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where('e.hospitalConfig ='.$this->globalOption->getHospitalConfig()->getId())
                    ->andWhere("e.service = 11")
                    ->andWhere("e.status = 1")
                    ->orderBy("e.name","ASC")
            ])

            ->add('specialization', EntityType::class, [
                'required'    => false,
                'class' => HmsServiceGroup::class,
                'property' => 'name',
                'empty_value' => '--- Select Specialization ---',
                'attr'=>['class'=>'span12 m-wrap select2 specialization'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where('e.hospitalConfig ='.$this->globalOption->getHospitalConfig()->getId())
                    ->andWhere("e.service = 12")
                    ->andWhere("e.status = 1")
                    ->orderBy("e.name","ASC")
            ])

            ->add('comment',TextareaType::class, ['attr'=>['class'=>'m-wrap span12 textarea','placeholder'=>'Add remarks','autocomplete'=>'off']])
            ->add('transactionMethod', EntityType::class, [
                'required'    => true,
                'class' => TransactionMethod::class,
                'property' => 'name',
                'attr'=>['class'=>'span12 m-wrap transactionMethod'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->andWhere("e.slug != 'cash-on-delivery'")
                    ->orderBy("e.id","ASC")
            ])
            ->add('paymentCard', EntityType::class, [
                'required'    => false,
                'property' => 'name',
                'class' => PaymentCard::class,
                'attr'=>['class'=>'span12 m-wrap'],
                'empty_value' => '---Choose payment card---',
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->orderBy("e.id","ASC")
            ])

            ->add('accountBank', EntityType::class, [
                'required'    => false,
                'class' => AccountBank::class,
                'property' => 'name',
                'attr'=>['class'=>'span12 m-wrap'],
                'empty_value' => '---Choose receive bank account---',
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('b')
                    ->where("b.status = 1")
                    ->andWhere("b.globalOption =".$this->globalOption->getId())
                    ->orderBy("b.name", "ASC")
            ])
            ->add('accountMobileBank', EntityType::class, [
                'required'    => false,
                'class' => AccountMobileBank::class,
                'property' => 'name',
                'attr'=>['class'=>'span12 m-wrap'],
                'empty_value' => '---Choose receive mobile bank account---',
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('b')
                    ->where("b.status = 1")
                    ->andWhere("b.globalOption =".$this->globalOption->getId())
                    ->orderBy("b.name", "ASC")
            ])
        ;
        if( $this->globalOption->getHospitalConfig()->isMarketingExecutive() == 1){

            $builder->add('marketingExecutive', EntityType::class, [
                'required'    => false,
                'class' => Particular::class,
                'property' => 'marketingExecutiveEmployee',
                'attr'=>['class'=>'span12 select2 m-wrap marketingExecutive'],
                'empty_value' => '--- Choose Marketing Executive ---',
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('b')
                    ->where("b.status = 1")
                    ->andWhere('b.service IN(:service)') ->setParameter('service',array_values([14]))
                    ->andWhere("b.hospitalConfig =".$this->globalOption->getHospitalConfig()->getId())
                    ->orderBy("b.name", "ASC")
            ]);
        }
        $builder->add('customer', PatientForDoctorAppointmentType::class);
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Invoice::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'appointment_invoice';
    }

    /**
     * @return mixed
     */
    protected function DepartmentChoiceList()
    {
        return $this->emCategory->getParentCategoryTree($parent = 7 /** Department */);

    }

}
