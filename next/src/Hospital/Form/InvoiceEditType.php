<?php

namespace App\Hospital\Form;

use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Hospital\Entity\Particular;
use App\Tool\Entity\TransactionMethod;
use App\Tool\Entity\PaymentCard;
use App\Accounting\Entity\AccountBank;
use App\Accounting\Entity\AccountMobileBank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Hospital\Entity\Invoice;
use App\DomainUser\Form\CustomerForHospitalType;
use Doctrine\ORM\EntityRepository;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class InvoiceEditType extends AbstractType
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

            ->add('cardNo',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Add payment card no','data-original-title'=>'Add payment card no','autocomplete'=>'off']])
            ->add('transactionId',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Add payment transaction id','data-original-title'=>'Add payment transaction id','autocomplete'=>'off']])
            ->add('paymentMobile',TextType::class, ['attr'=>['class'=>'m-wrap span12 mobile','placeholder'=>'Add payment mobile no','data-original-title'=>'Add payment mobile no','autocomplete'=>'off']])
            ->add('payment',TextType::class, ['attr'=>['class'=>'tooltips payment span11 input2 m-wrap','data-trigger' => 'hover','placeholder'=>'Receive','data-original-title'=>'Enter received amount','autocomplete'=>'off']])
            ->add('discountCalculation',TextType::class, ['attr'=>['class'=>'tooltips initialDiscount span11 input2 m-wrap','data-trigger' => 'hover','placeholder'=>'Discount','data-original-title'=>'Enter discount amount','autocomplete'=>'off']])
            ->add('assignDoctor', EntityType::class, [
                'required'    => false,
                'class' => Particular::class,
                'choice_label' => 'doctor',
                'placeholder' => '---Select Assign Doctor---',
                'attr'=>['class'=>'span12 m-wrap select2'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where('e.hospitalConfig ='.$this->globalOption->getHospitalConfig()->getId())
                    ->andWhere("e.service = 5")
                    ->andWhere("e.status = 1")
                    ->orderBy("e.name","ASC")
            ])
            /*->add('discount', HiddenType::class)*/
            ->add('comment',TextType::class, ['attr'=>['class'=>'m-wrap span12 textarea','placeholder'=>'Add remarks','autocomplete'=>'off']])
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
        $builder->add('customer', CustomerForHospitalType::class);
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
            'data_class' => Invoice::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'hospitalInvoice';
    }

}
