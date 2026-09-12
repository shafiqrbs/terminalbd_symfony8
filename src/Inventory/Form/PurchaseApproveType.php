<?php

namespace App\Inventory\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Tool\Entity\TransactionMethod;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use App\Accounting\Entity\AccountBank;
use App\Accounting\Entity\AccountMobileBank;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Inventory\Entity\Purchase;
use App\Inventory\Entity\InventoryConfig;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class PurchaseApproveType extends AbstractType
{

    public  $inventoryConfig;

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->inventoryConfig = $options['inventoryConfig'];

        $builder

            ->add('transactionMethod', EntityType::class, [
                'required'    => true,
                'class' => TransactionMethod::class,
                'choice_label' => 'name',
                'attr'=>['class'=>'span12 m-wrap transactionMethod'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required')
                ],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->orderBy("e.id","ASC")
            ])
            ->add('totalAmount',TextType::class, ['attr'=>['class'=>' m-wrap span12 numeric','placeholder'=>'Net total amount BDT']])
            ->add('dueAmount',TextType::class, ['attr'=>['class'=>' m-wrap span12 numeric','placeholder'=>'Due amount BDT']])
            ->add('accountBank', EntityType::class, [
                'required'    => true,
                'class' => AccountBank::class,
                'placeholder' => '---Choose a bank---',
                'choice_label' => 'name',
                'attr'=>['class'=>'span12 m-wrap'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('b')
                    ->where("b.status = 1")
                    ->andWhere("b.globalOption =".$this->inventoryConfig->getGlobalOption()->getId())
                    ->orderBy("b.name", "ASC"),
            ])
            ->add('accountMobileBank', EntityType::class, [
                'required'    => true,
                'class' => AccountMobileBank::class,
                'placeholder' => '---Choose a mobile banking---',
                'choice_label' => 'name',
                'attr'=>['class'=>'span12 m-wrap'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('b')
                    ->where("b.status = 1")
                    ->andWhere("b.globalOption =".$this->inventoryConfig->getGlobalOption()->getId())
                    ->orderBy("b.name", "ASC"),
            ])
            ->add('paymentAmount',TextType::class, ['attr'=>['class'=>'m-wrap span12 numeric','placeholder'=>'Net payment amount BDT'],
                'constraints' =>[
                    new NotBlank(message: 'Please add payment amount BDT')
                ]])
            ->add('purchaseTo', ChoiceType::class, [
                'required'    => false,
                'attr'=>['class'=>'span12 m-wrap'],
                'placeholder' => '---Choose a purchase To ---',
                'choices' => ['International' => 'International', 'National' => 'National', 'In House' => 'In House'],
            ])
            ->add('receiveDate', DateType::class, [
                'widget' => 'single_text',
                'placeholder' => [
                    'mm' => 'mm', 'dd' => 'dd','YY' => 'YY'

                ],
                'format' => 'dd-MM-yyyy',
                'attr' => ['class'=>'m-wrap span12 datePicker purchaseInput'],
                'view_timezone' => 'Asia/Dhaka'])

            ->add('asInvestment');
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'inventoryConfig' => null,
        ]);

        $resolver->setDefaults([
            'data_class' => Purchase::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'purchase';
    }
}
