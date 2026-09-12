<?php

namespace App\Inventory\Form;

use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Inventory\Entity\PurchaseItem;
use App\Inventory\Entity\InventoryConfig;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class PurchaseItemType extends AbstractType
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
            ->add('serialNo',TextareaType::class, ['attr'=>['class'=>'m-wrap span12','rows'=>3,'placeholder'=>'Attribute name']])
            ->add('expiredDate', DateType::class, [
                'widget' => 'single_text',
                'placeholder' => [
                    'mm' => 'mm', 'dd' => 'dd','YY' => 'YY'

                ],
                'format' => 'dd-MM-yyyy',
                'attr' => ['class'=>'m-wrap span12 dateCalendar'],
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
