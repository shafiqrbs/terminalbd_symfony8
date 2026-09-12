<?php

namespace App\Accounting\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Accounting\Entity\AccountHead;
use App\Accounting\Entity\ExpenseCategory;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Accounting\Repository\AccountHeadRepository;
use App\Accounting\Repository\ExpenseCategoryRepository;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class ExpenseCategoryType extends AbstractType
{
    protected $em;
    protected $accountHead;
    protected $globalOption;


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->em = $options['em'];
        $this->accountHead = $options['accountHead'];
        $this->globalOption = $options['globalOption'];

        $builder

            ->add('name',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter expense category name'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required'),

                ]
            ])
            ->add('accountHead', EntityType::class, [
                'required'    => true,
                'class' => AccountHead::class,
                'placeholder' => '---Choose a account head---',
                'choice_label' => 'name',
                'attr'=>['class'=>'span12 select2 m-wrap'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required')
                ],
                'choices'=> $this->ExpenseAccountChoiceList()
            ])

            ->add('parent', EntityType::class, [
                'required'    => true,
                'placeholder' => '---Select parent expense category---',
                'attr'=>['class'=>'ExpenseCategory m-wrap span12'],
                'class' => ExpenseCategory::class,
                'choice_label' => 'nestedLabel',
                'choices'=> $this->ExpenseCategoryChoiceList()
            ])
            ->add('status')

        ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'em' => null,
            'accountHead' => null,
            'globalOption' => null,
        ]);

        $resolver->setDefaults([
            'data_class' => ExpenseCategory::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'product_bundle_accountingbundle_expensecategory';
    }

    /**
     * @return mixed
     */
    protected function ExpenseCategoryChoiceList()
    {
        return $this->em->getFlatExpenseCategoryTree($this->globalOption);

    }

    /**
     * @return mixed
     */
    protected function ExpenseAccountChoiceList()
    {
        return $this->accountHead->getAccountHeadTrees();

    }

}
