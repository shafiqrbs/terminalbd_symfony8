<?php

namespace App\Accounting\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Tool\Entity\TransactionMethod;
use App\Accounting\Entity\AccountHead;
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

class AccountJournalType extends AbstractType
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
            ->add('transactionType', ChoiceType::class, [
                'attr'=>['class'=>'span12 m-wrap'],
                'constraints' =>[
                    new NotBlank(message: 'Please choose required')
                ],
                'choices' => ['In' => 'Debit', 'Out' => 'Credit'],
            ])
            ->add('transactionMethod', EntityType::class, [
                'required'    => true,
                'class' => TransactionMethod::class,
                'placeholder' => '---Choose a transaction method---',
                'choice_label' => 'name',
                'attr'=>['class'=>'span12 m-wrap transactionMethod'],
               /* 'constraints' =>array(
                    new NotBlank(array('message'=>'Please input required'))
                ),*/
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->andWhere("e.slug != 'cash-on-delivery'")
                    ->orderBy("e.id")
            ])

            ->add('accountHeadDebit', EntityType::class, [
                'class'     => AccountHead::class,
                'group_by'  => 'parent.name',
                'choice_label'  => 'name',
                'constraints' =>[
                    new NotBlank(message: 'Please choose debit account')
                ],
                'placeholder' => '---Choose a debit account---',
                'attr'=>['class'=>'span12 m-wrap select2'],
                'choice_translation_domain' => true,
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->join("e.parent",'c')
                    ->where("e.status = 1")
                    ->andWhere("c.isParent =1")
                    ->orderBy("e.name", "ASC")
            ])

            ->add('accountHeadCredit', EntityType::class, [
                'class'     => AccountHead::class,
                'group_by'  => 'parent.name',
                'choice_label'  => 'name',
                'constraints' =>[
                    new NotBlank(message: 'Please choose credit account')
                ],
                'placeholder' => '---Choose a credit account---',
                'attr'=>['class'=>'span12 m-wrap select2'],
                'choice_translation_domain' => true,
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->join("e.parent",'c')
                    ->where("e.status = 1")
                    ->andWhere("c.isParent =1")
                    ->orderBy("e.name", "ASC")
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
                'placeholder' => '--- Select a account user name ---',
                'choice_label' => 'userFullName',
                'attr'=>['class'=>'span12 m-wrap'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('u')
	                          ->where("u.isDelete != 1")
	                          ->andWhere("u.domainOwner = 2")
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
        return 'appstore_bundle_accountingbundle_AccountJournal';
    }

}
