<?php

namespace App\Accounting\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Tool\Entity\TransactionMethod;
use App\Accounting\Entity\AccountBank;
use App\Accounting\Entity\AccountMobileBank;
use App\User\Entity\User;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Accounting\Entity\PettyCash;
use Doctrine\ORM\EntityRepository;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class PettyCashType extends AbstractType
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

            ->add('amount',TextType::class, ['attr'=>['class'=>'m-wrap span12 numeric','placeholder'=>'Add payment amount BDT'],
                'constraints' =>[
                    new NotBlank(message: 'Please add payment amount BDT')
                ]])
            ->add('remark',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'']])
            ->add('transactionMethod', EntityType::class, [
                'required'    => true,
                'class' => TransactionMethod::class,
                'placeholder' => '---Choose a transaction method---',
                'choice_label' => 'name',
                'attr'=>['class'=>'span12 select2 transactionMethod'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required')
                ],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->orderBy("e.name")
            ])
            ->add('accountBank', EntityType::class, [
                'required'    => true,
                'class' => AccountBank::class,
                'placeholder' => '---Choose a bank---',
                'choice_label' => 'name',
                'attr'=>['class'=>'span12 select2'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.globalOption =".$this->globalOption->getId()),
            ])

            ->add('accountMobileBank', EntityType::class, [

                'required'    => true,
                'class' => AccountMobileBank::class,
                'placeholder' => '---Choose a mobile account---',
                'choice_label' => 'name',
                'attr'=>['class'=>'span12 select2'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.globalOption =".$this->globalOption->getId()),
            ])

            ->add('toUser', EntityType::class, [
                'required'    => true,
                'class' => User::class,
                'placeholder' => '---Choose a customer---',
                'choice_label' => 'username',
                'attr'=>['class'=>'span12 select2'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required')
                ],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.globalOption =".$this->globalOption->getId()),
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
            'data_class' => PettyCash::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'appstore_bundle_accountingbundle_PettyCash';
    }

}
