<?php

namespace App\Inventory\Form;

use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Inventory\Entity\ItemTypeGrouping;
use Symfony\Component\Form\AbstractType;

class ItemTypeGroupingType extends AbstractType
{

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ItemTypeGrouping::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'appstore_bundle_inventorybundle_itemtypegrouping';
    }
}
