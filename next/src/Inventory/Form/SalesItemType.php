<?php

namespace App\Inventory\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Inventory\Entity\SalesItem;
use App\Inventory\Entity\InventoryConfig;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class SalesItemType extends AbstractType
{

    /** @var InventoryConfig */

    public  $inventoryConfig;

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->inventoryConfig = $options['inventoryConfig'];


        $builder
        ->add('item',TextType::class, ['attr'=>['class'=>'m-wrap span12 select2Item stockItem inputs','placeholder'=>'Enter product name or code','data-original-title'=>'Enter payment amount','autocomplete'=>'off']]);

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
            'data_class' => SalesItem::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'salesitem';
    }

}
