<?php

namespace App\Accounting\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Tool\Entity\TransactionMethod;
use App\Accounting\Entity\AccountBank;
use App\Accounting\Entity\AccountMobileBank;
use App\User\Entity\User;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Accounting\Entity\AccountJournal;
use Doctrine\ORM\EntityRepository;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class ProfitWithdrawalType extends AbstractType
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
            ->add('remark',TextareaType::class, ['attr'=>['class'=>'m-wrap span12','rows'=>'5','placeholder'=>'Enter narration']])
            ->add('transactionMethod', EntityType::class, [
                'required'    => true,
                'class' => TransactionMethod::class,
                'placeholder' => '---Choose a transaction method---',
                'choice_label' => 'name',
                'attr'=>['class'=>'span12 m-wrap transactionMethod'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required')
                ],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->andWhere("e.slug != 'cash-on-delivery'")
                    ->orderBy("e.id")
            ])
            ->add('accountBank', EntityType::class, [
                'required'    => true,
                'class' => AccountBank::class,
                'placeholder' => '---Choose a bank---',
                'choice_label' => 'name',
                'attr'=>['class'=>'span12 m-wrap'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->andWhere("e.globalOption =".$this->globalOption->getId()),
            ])
            ->add('accountMobileBank', EntityType::class, [
                'required'    => true,
                'class' => AccountMobileBank::class,
                'placeholder' => '---Choose a mobile account---',
                'choice_label' => 'name',
                'attr'=>['class'=>'span12 m-wrap'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->andWhere("e.globalOption =".$this->globalOption->getId()),
            ])

            ->add('toUser', EntityType::class, [
                'required'    => true,
                'class' => User::class,
                'placeholder' => '--- Select a account stakeholder ---',
                'choice_label' => 'userFullName',
                'constraints' =>[
                    new NotBlank(message: 'Please input required')
                ],
                'attr'=>['class'=>'span12 m-wrap userProfit'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('u')
	                          ->join("u.profile","profile")
	                          ->where("u.isDelete != 1")
	                          ->andWhere("u.domainOwner = 2")
	                          ->andWhere("profile.userGroup = 'stakeholder'")
	                          ->andWhere("u.globalOption =".$this->globalOption->getId())
	                          ->orderBy("u.username", "ASC"),
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
            'data_class' => AccountJournal::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'withdrawal';
    }

}
