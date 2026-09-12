<?php

namespace App\Restaurant\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Tool\Entity\TransactionMethod;
use App\Tool\Entity\PaymentCard;
use App\Accounting\Entity\AccountBank;
use App\User\Entity\User;
use App\Accounting\Entity\AccountMobileBank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Restaurant\Entity\Invoice;
use Doctrine\ORM\EntityRepository;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class TemporaryType extends AbstractType
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

            ->add('payment',TextType::class, ['attr'=>['class'=>'m-wrap span12 payment','placeholder'=>'Payment','data-original-title'=>'Add payment amount','autocomplete'=>'off']])
            ->add('cardNo',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Add payment card no','data-original-title'=>'Add payment card no','autocomplete'=>'off']])
            ->add('transactionId',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Add payment transaction id','data-original-title'=>'Add payment transaction id','autocomplete'=>'off']])
            ->add('paymentMobile',TextType::class, ['attr'=>['class'=>'m-wrap span12 mobile','placeholder'=>'Add payment mobile no','data-original-title'=>'Add payment mobile no','autocomplete'=>'off']])
            ->add('slipNo',TextType::class, ['attr'=>['class'=>'m-wrap span12 tooltips','data-trigger' => 'hover','placeholder'=>'Add slip no','data-original-title'=>'Add slip no','autocomplete'=>'off']])
            ->add('discount',HiddenType::class,['attr'=>['class'=>'discount']])
            ->add('isHold')
            ->add('remark',TextType::class, ['attr'=>['class'=>'tooltips remark span12 m-wrap input2','data-trigger' => 'hover','placeholder'=>'Enter narration','data-original-title'=>'Enter discount narration','autocomplete'=>'off']])
            ->add('discountCalculation',TextType::class, ['attr'=>['class'=>'tooltips span12 m-wrap discountCalculation','data-trigger' => 'hover','placeholder'=>'Discount','data-original-title'=>'Enter discount amount','autocomplete'=>'off']])
            ->add('discountCoupon',TextType::class, ['attr'=>['class'=>'tooltips span12 m-wrap discountCoupon','data-trigger' => 'hover','placeholder'=>'Coupon No','data-original-title'=>'Enter Discount Coupon No','autocomplete'=>'off']])
            ->add('discountType', ChoiceType::class, [
                'attr'=>['class'=>'span12 m-wrap'],
                'choices'   => ['Flat' => 'flat', 'Percentage' => 'percentage'],
                'required'  => true,
            ])
/*            ->add('tokenNo', EntityType::class, array(
                'required'    => false,
                'class' => 'App\Restaurant\Entity\Particular',
                'choice_label' => 'name',
                'placeholder' => '---Select Table no ---',
                'attr'=>array('class'=>'span12 m-wrap'),
                'query_builder' => function(EntityRepository $er){
                    return $er->createQueryBuilder('e')
                        ->join("e.service",'s')
                        ->where("e.status = 1")
                        ->andWhere('s.slug IN (:slugs)')
                        ->setParameter('slugs',array('token'))
                        ->andWhere("e.restaurantConfig ={$this->config}")
                        ->orderBy("e.name","ASC");
                }
            ))*/
            ->add('transactionMethod', EntityType::class, [
                'required'    => true,
                'class' => TransactionMethod::class,
                'choice_label' => 'name',
                'attr'=>['class'=>'span12 m-wrap transactionMethod'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
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

            ->add('salesBy', EntityType::class, [
                'required'    => true,
                'class' => User::class,
                'choice_label' => 'userFullName',
                'attr'=>['class'=>'span12 m-wrap'],
                'placeholder' => '---Served By---',
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
                'attr'=>['class'=>'span12 m-wrap'],
                'placeholder' => '---Choose receive mobile bank account---',
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
            'data_class' => Invoice::class
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
