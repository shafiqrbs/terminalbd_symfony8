<?php

namespace App\Inventory\Form;

use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Inventory\Entity\PurchaseReturn;
use Symfony\Component\Form\AbstractType;

class PurchaseReturnType extends AbstractType
{

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PurchaseReturn::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'appstore_bundle_inventorybundle_purchasereturn';
    }
}
