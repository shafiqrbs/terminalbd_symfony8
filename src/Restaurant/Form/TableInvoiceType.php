<?php

namespace App\Restaurant\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Tool\Entity\PaymentCard;
use App\Accounting\Entity\AccountBank;
use App\User\Entity\User;
use App\Accounting\Entity\AccountMobileBank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Restaurant\Entity\RestaurantTableInvoice;
use Doctrine\ORM\EntityRepository;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class TableInvoiceType extends AbstractType
{
    protected $globalOption;
    protected $config;


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->globalOption = $options['globalOption'];
        $this->config = $this->globalOption->getRestaurantConfig()->getId();

        $builder

            ->add('payment',TextType::class, ['attr'=>['class'=>'m-wrap span12 input-number payment tooltips','data-trigger' => 'hover','placeholder'=>'Receive BDT','data-original-title'=>'Add receive amount','autocomplete'=>'off']])
            ->add('cardNo',TextType::class, ['attr'=>['class'=>'m-wrap span12 invoice-change','placeholder'=>'Card no','data-original-title'=>'Add payment card no','autocomplete'=>'off']])
            ->add('transactionId',TextType::class, ['attr'=>['class'=>'m-wrap span12 invoice-change','placeholder'=>'Payment ID','data-original-title'=>'Add payment transaction id','autocomplete'=>'off']])
            ->add('paymentMobile',TextType::class, ['attr'=>['class'=>'m-wrap span12 mobile invoice-change','placeholder'=>'Payment Mobile','data-original-title'=>'Add payment mobile no','autocomplete'=>'off']])

            ->add('discount',HiddenType::class,['attr'=>['class'=>'discount']])
            ->add('discountType', ChoiceType::class, [
                'attr'=>['class'=>'span12 m-wrap invoice-change'],
                'choices'   => ['Flat' => 'flat', 'Percentage' => 'percentage'],
                'required'  => true,
            ])
            ->add('discountCalculation',TextType::class, ['attr'=>['class'=>'tooltips span12 m-wrap input-number invoice-change ','data-trigger' => 'hover','placeholder'=>'Discount','data-original-title'=>'Enter discount amount','autocomplete'=>'off']])
            ->add('discountCoupon',TextType::class, ['attr'=>['class'=>'tooltips span12 m-wrap input-number discountCoupon invoice-change','data-trigger' => 'hover','placeholder'=>'Coupon No','data-original-title'=>'Enter Discount Coupon No','autocomplete'=>'off']])
            ->add('paymentCard', EntityType::class, [
                'required'    => false,
                'choice_label' => 'name',
                'class' => PaymentCard::class,
                'attr'=>['class'=>'span12 m-wrap invoice-change'],
                'placeholder' => '---Payment card---',
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->orderBy("e.id","ASC")
            ])
            ->add('accountBank', EntityType::class, [
                'required'    => false,
                'class' => AccountBank::class,
                'choice_label' => 'name',
                'attr'=>['class'=>'span12 m-wrap invoice-change'],
                'placeholder' => '---Choose Bank Account---',
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('b')
                    ->where("b.status = 1")
                    ->andWhere("b.globalOption =".$this->globalOption->getId())
                    ->orderBy("b.name", "ASC")
            ])

            ->add('salesBy', EntityType::class, [
                'required'    => true,
                'class' => User::class,
                'choice_label' => 'userFullName',
                'attr'=>['class'=>'span12 m-wrap sales-by invoice-change'],
                'placeholder' => '---Order Taken By---',
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('u')
                    ->where("u.isDelete != 1")
                    ->andWhere("u.enabled = 1")
                    ->andWhere("u.domainOwner = 2")
                    ->andWhere("u.globalOption =".$this->globalOption->getId())
                    ->orderBy("u.username", "ASC")
            ])

            ->add('accountMobileBank', EntityType::class, [
                'required'    => false,
                'class' => AccountMobileBank::class,
                'choice_label' => 'name',
                'attr'=>['class'=>'span12 m-wrap invoice-change'],
                'placeholder' => '---Choose Mobile Account---',
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('b')
                    ->where("b.status = 1")
                    ->andWhere("b.globalOption =".$this->globalOption->getId())
                    ->orderBy("b.name", "ASC")
            ]);
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
            'data_class' => RestaurantTableInvoice::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'restaurant_invoice';
    }

}
