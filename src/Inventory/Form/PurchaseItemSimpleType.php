<?php

namespace App\Inventory\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Inventory\Entity\PurchaseItem;
use App\Inventory\Entity\InventoryConfig;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class PurchaseItemSimpleType extends AbstractType
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

            ->add('item',TextType::class, [
                'attr'=>['class'=>'itemInput m-wrap span12 selectAllItem','placeholder'=>'Select Item Name'],
                'mapped'=>'false'
            ])

            ->add('quantity',TextType::class, ['attr'=>['class'=>'itemInput m-wrap span12','placeholder'=>'Quantity'],
                'constraints' =>[
                    new NotBlank(message: 'Add item quantity')]])

            ->add('salesPrice',TextType::class, ['attr'=>['class'=>'itemInput m-wrap span6','placeholder'=>'Sales price'],
                ])

            ->add('purchaseSubTotal',TextType::class, ['attr'=>['class'=>'itemInput m-wrap span12','placeholder'=>'Purchase price'],
                'constraints' =>[
                    new NotBlank(message: 'Add purchase price')]]);

        if($this->inventoryConfig->isAttribute() == 1){
            $builder
                ->add('serialNo',TextareaType::class, ['required'=> false,'attr'=>['class'=>'m-wrap span12','required' => false,'rows'=>5,'placeholder'=>'Attribute name']])
                ->add('expiredDate', DateType::class, [
                    'widget' => 'single_text',
                    'required'    => false,
                    'placeholder' => [
                        'mm' => 'mm', 'dd' => 'dd','YY' => 'YY'

                    ],
                    'format' => 'dd-MM-yyyy',
                    'attr' => ['class'=>'m-wrap span12 dateCalendar','autocomplete' => false,'required' => false],
                    'view_timezone' => 'Asia/Dhaka'])

                ->add('assuranceFromVendor', ChoiceType::class, [
                    'attr'=>['class'=>'span12 m-wrap'],
                    'choices' => ['1 Month' => '1 Month', '3 Month' => '3 Month', '4 Month' => '4 Month', '6 Month' => '6 Month', '1 Year' => '1 Year', '1 Year 6 Month' => '1 Year 6 Month', '2 Year' => '2 Year', '2 Year 6 Month' => '2 Year 6 Month', '3 Year' => '3 Year', '3 Year 6 Month' => '3 Year 6 Month', '4 Year' => '4 Year', '4 Year 6 Month' => '4 Year 6 Month', '5 Year' => '5 Year', '5 Year 6 Month' => '5 Year 6 Month', 'Product Life Time' => 'Product Life Time', 'No Warranty' => 'No Warranty'],
                ])
                ->add('assuranceToCustomer', ChoiceType::class, [
                    'attr'=>['class'=>'span12 m-wrap'],
                    'choices' => ['1 Month' => '1 Month', '3 Month' => '3 Month', '4 Month' => '4 Month', '6 Month' => '6 Month', '1 Year' => '1 Year', '1 Year 6 Month' => '1 Year 6 Month', '2 Year' => '2 Year', '2 Year 6 Month' => '2 Year 6 Month', '3 Year' => '3 Year', '3 Year 6 Month' => '3 Year 6 Month', '4 Year' => '4 Year', '4 Year 6 Month' => '4 Year 6 Month', '5 Year' => '5 Year', '5 Year 6 Month' => '5 Year 6 Month', 'Product Life Time' => 'Product Life Time', 'No Warranty' => 'No Warranty'],
                ])
                ->add('assuranceType', ChoiceType::class, [
                    'attr'=>['class'=>'span12 m-wrap'],
                    'choices' => ['Warranty' => 'Warranty', 'Grantee' => 'Grantee', 'No Warranty' => 'No Warranty'],
                ]);
        }

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
            'data_class' => PurchaseItem::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'purchaseitem';
    }
}
