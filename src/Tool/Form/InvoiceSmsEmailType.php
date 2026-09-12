<?php

namespace App\Tool\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Tool\Entity\TransactionMethod;
use App\Tool\Entity\PortalBankAccount;
use App\Tool\Entity\PortalMobileBankAccount;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Tool\Entity\InvoiceSmsEmail;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class InvoiceSmsEmailType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('paymentMobile',TextType::class, ['attr'=>['class'=>'m-wrap span12 mobile','placeholder'=>'Add payment mobile no','data-original-title'=>'Add payment mobile no','autocomplete'=>'off']])
            ->add('transaction',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Add payment transaction id','data-original-title'=>'Add payment transaction id','autocomplete'=>'off']])
            ->add('accountNo',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Add account no','data-original-title'=>'Add payment transaction id','autocomplete'=>'off']])

            ->add('transactionMethod', EntityType::class, [
                'required'    => false,
                'class' => TransactionMethod::class,
                'choice_label' => 'name',
                'constraints' =>[
                    new NotBlank(message: 'Select transaction method')
                ],
                'attr'=>['class'=>'span12 m-wrap transactionMethod'],
                'placeholder' => '---Choose mobile bank account---',
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('b')
                    ->where("b.status = 1")
                    ->orderBy("b.name", "ASC")
            ])

            ->add('portalBankAccount', EntityType::class, [
                'required'    => false,
                'class' => PortalBankAccount::class,
                'choice_label' => 'name',
                'attr'=>['class'=>'span12 m-wrap select2'],
                'placeholder' => '---Choose mobile bank account---',
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('b')
                    ->where("b.status = 1")
                    ->orderBy("b.name", "ASC")
            ])
            ->add('portalMobileBankAccount', EntityType::class, [
                'required'    => false,
                'class' => PortalMobileBankAccount::class,
                'choice_label' => 'name',
                'attr'=>['class'=>'span12 m-wrap select2'],
                'placeholder' => '---Choose mobile bank account---',
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('b')
                    ->where("b.status = 1")
                    ->orderBy("b.name", "ASC")
            ]);
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => InvoiceSmsEmail::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'setting_bundle_toolbundle_invoicesmsemail';
    }
}
