<?php

namespace App\Accounting\Form;

use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Tool\Entity\TransactionMethod;
use App\DomainUser\Entity\Customer;
use App\Accounting\Entity\AccountBank;
use App\Accounting\Entity\AccountMobileBank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class AccountOnlineOrderType extends AbstractType
{

    public  $globalOption;

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->globalOption = $options['globalOption'];

        $builder

            ->add('amount',TextType::class, ['attr'=>['class'=>'m-wrap span12 numeric','placeholder'=>'Add Received Amount BDT'],
                'constraints' =>[
                    new NotBlank(message: 'Please add payment amount BDT')
                ]])
            ->add('remark',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Add Remark']])
            ->add('transactionMethod', EntityType::class, [
                'required'    => true,
                'class' => TransactionMethod::class,
                'placeholder' => '---Choose a Transaction Method---',
                'choice_label' => 'name',
                'attr'=>['class'=>'span12 select2 transactionMethod'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required')
                ],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->andWhere("e.slug != 'cash-on-delivery'")
                    ->orderBy("e.id")
            ])
            ->add('customer', EntityType::class, [
                'required'    => true,
                'class' => Customer::class,
                'placeholder' => '---Choose a customer---',
                'choice_label' => 'name',
                'attr'=>['class'=>'span12 select2'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required')
                ],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.globalOption =".$this->globalOption->getId()),
            ])
            ->add('accountBank', EntityType::class, [
               'required'    => true,
               'class' => AccountBank::class,
               'placeholder' => '---Choose a bank---',
               'choice_label' => 'name',
               'attr'=>['class'=>'span12 select2'],
               'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('b')
                   ->where("b.status = 1")
                   ->andWhere("b.globalOption =".$this->globalOption->getId())
                   ->orderBy("b.name", "ASC"),
           ])
            ->add('accountMobileBank', EntityType::class, [
                'required'    => true,
                'class' => AccountMobileBank::class,
                'placeholder' => '---Choose a mobile banking---',
                'choice_label' => 'name',
                'attr'=>['class'=>'span12 select2'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('b')
                    ->where("b.status = 1")
                    ->andWhere("b.globalOption =".$this->globalOption->getId())
                    ->orderBy("b.name", "ASC"),
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
            'data_class' => 'App\Accounting\Entity\AccountOnlineOrder'
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'appstore_bundle_accountingbundle_AccountOnlineOrder';
    }

}
