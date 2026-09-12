<?php

namespace App\Accounting\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Tool\Entity\TransactionMethod;
use App\Accounting\Entity\AccountBank;
use App\Accounting\Entity\AccountMobileBank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Accounting\Entity\AccountBalanceTransfer;
use Doctrine\ORM\EntityRepository;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class AccountBalanceTransferType extends AbstractType
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

            ->add('amount',TextType::class, ['attr'=>['class'=>'m-wrap span12 numeric','placeholder'=>'Add amount'],
                'constraints' =>[
                    new NotBlank(message: 'Please add amount')
                ]])
            ->add('remark',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'']])
            ->add('toTransactionMethod', EntityType::class, [
                'required'    => true,
                'class' => TransactionMethod::class,
                'placeholder' => '---Choose a Transaction Method---',
                'choice_label' => 'name',
                'attr'=>['class'=>'span12 m-wrap toTransactionMethod'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required')
                ],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->andWhere("e.slug != 'cash-on-delivery'")
                    ->orderBy("e.id")
            ])

            ->add('toAccountBank', EntityType::class, [
                'required'    => true,
                'class' => AccountBank::class,
                'placeholder' => '---Choose a bank---',
                'choice_label' => 'name',
                'attr'=>['class'=>'span12 m-wrap'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->andWhere("e.globalOption =".$this->globalOption->getId()),
            ])

            ->add('toAccountMobileBank', EntityType::class, [
                'required'    => true,
                'class' => AccountMobileBank::class,
                'placeholder' => '---Choose a mobile account---',
                'choice_label' => 'name',
                'attr'=>['class'=>'span12 m-wrap'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->andWhere("e.globalOption =".$this->globalOption->getId()),
            ])
			->add('fromTransactionMethod', EntityType::class, [
                'required'    => true,
                'class' => TransactionMethod::class,
                'placeholder' => '---Choose a Transaction Method---',
                'choice_label' => 'name',
                'attr'=>['class'=>'span12 m-wrap fromTransactionMethod'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required')
                ],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->andWhere("e.slug != 'cash-on-delivery'")
                    ->orderBy("e.id")
            ])

            ->add('fromAccountBank', EntityType::class, [
                'required'    => true,
                'class' => AccountBank::class,
                'placeholder' => '---Choose a bank---',
                'choice_label' => 'name',
                'attr'=>['class'=>'span12 m-wrap'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->andWhere("e.globalOption =".$this->globalOption->getId()),
            ])

            ->add('fromAccountMobileBank', EntityType::class, [
                'required'    => true,
                'class' => AccountMobileBank::class,
                'placeholder' => '---Choose a mobile account---',
                'choice_label' => 'name',
                'attr'=>['class'=>'span12 m-wrap'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->andWhere("e.globalOption =".$this->globalOption->getId()),
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
            'data_class' => AccountBalanceTransfer::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'balanceTransfer';
    }

}
