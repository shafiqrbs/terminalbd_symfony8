<?php

namespace App\Medicine\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Tool\Entity\TransactionMethod;
use App\Tool\Entity\PaymentCard;
use App\Accounting\Entity\AccountBank;
use App\Accounting\Entity\AccountMobileBank;
use App\User\Entity\User;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Medicine\Entity\MedicineSales;
use Doctrine\ORM\EntityRepository;
use App\Location\Repository\LocationRepository;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class SalesTemporaryType extends AbstractType
{
    protected $globalOption;
    protected $location;


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->globalOption = $options['globalOption'];
        $this->location = $options['location'];

        $builder

            ->add('cardNo',TextType::class, ['attr'=>['class'=>'m-wrap span12 salesInput','placeholder'=>'Add payment card no','data-original-title'=>'Add payment card no','autocomplete'=>'off']])
            ->add('received',NumberType::class, ['attr'=>['class'=>'m-wrap span12 salesInput','placeholder'=>'Received','data-original-title'=>'Add payment received','autocomplete'=>'off']])
            ->add('discountCalculation',NumberType::class, ['attr'=>['class'=>'m-wrap span12 salesInput initialDiscount','placeholder'=>'Discount','data-original-title'=>'Add payment discount','autocomplete'=>'off']])
            ->add('discount',HiddenType::class)
            ->add('due',HiddenType::class)
            ->add('paymentMobile',TextType::class, ['attr'=>['class'=>'m-wrap span12 mobile salesInput','placeholder'=>'Add payment mobile no','data-original-title'=>'Add payment mobile no','autocomplete'=>'off']])
            ->add('transactionId',TextType::class, ['attr'=>['class'=>'m-wrap span12 salesInput','placeholder'=>'Add payment transaction id','data-original-title'=>'Add payment transaction id','autocomplete'=>'off']])
            ->add('transactionMethod', EntityType::class, [
                'required'    => true,
                'class' => TransactionMethod::class,
                'choice_label' => 'name',
                'attr'=>['class'=>'salesInput span12 m-wrap transactionMethod'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->andWhere("e.slug != 'cash-on-delivery'")
                    ->orderBy("e.id","ASC")
            ])
            ->add('paymentCard', EntityType::class, [
                'required'    => false,
                'choice_label' => 'name',
                'class' => PaymentCard::class,
                'attr'=>['class'=>'salesInput span12 m-wrap'],
                'placeholder' => '---Choose payment card---',
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->orderBy("e.id","ASC")
            ])

            ->add('accountBank', EntityType::class, [
                'required'    => false,
                'class' => AccountBank::class,
                'choice_label' => 'name',
                'attr'=>['class'=>'salesInput span12 m-wrap'],
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
                'attr'=>['class'=>'span12 m-wrap salesInput'],
                'placeholder' => '---Choose receive mobile bank account---',
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('b')
                    ->where("b.status = 1")
                    ->andWhere("b.globalOption =".$this->globalOption->getId())
                    ->orderBy("b.name", "ASC")
            ])
            ->add('salesBy', EntityType::class, [
                'required'    => false,
                'class' => User::class,
                'choice_label' => 'userFullName',
                'attr'=>['class'=>'span12 m-wrap salesInput'],
                'placeholder' => '---Choose sales by---',
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('u')
                    ->where("u.isDelete != 1")
                    ->andWhere("u.enabled = 1")
                    ->andWhere("u.domainOwner = 2")
                    ->andWhere("u.globalOption =".$this->globalOption->getId())
                    ->orderBy("u.username", "ASC")
            ])
            ->add('discountType', ChoiceType::class, [
                'attr'=>['class'=>'m-wrap discount-type span12'],
                'expanded'      =>false,
                'multiple'      =>false,
                'choices' => ['Flat' => 'flat', 'Percentage' => 'percentage'],
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
            'location' => null,
        ]);

        $resolver->setDefaults([
            'data_class' => MedicineSales::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'salesTemporary';
    }

}
