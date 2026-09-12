<?php

namespace App\Inventory\Form;

use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Inventory\Entity\BranchInvoice;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class BranchInvoiceType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('branchItem', BranchInvoiceItemType::class);

    }
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BranchInvoice::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'appstore_bundle_inventorybundle_branchinvoice';
    }


}
