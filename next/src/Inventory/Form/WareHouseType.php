<?php

namespace App\Inventory\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Inventory\Entity\WareHouse;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Inventory\Entity\InventoryConfig;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class WareHouseType extends AbstractType
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
            ->add('name')
            ->add('parent', EntityType::class, [
                'required'    => false,
                'class' => WareHouse::class,
                'placeholder' => '---Select warehouse---',
                'choice_label' => 'name',
                'attr'=>['class'=>'selectbox'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('w')
                    ->where("w.status = 1")
                    ->andWhere("w.isWareHouse = 1")
                    ->andWhere("w.inventoryConfig =". $this->inventoryConfig->getId())
                    ->orderBy('w.name','ASC'),
            ])
            ->add('isWareHouse')
            ->add('status')
        ;
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
            'data_class' => WareHouse::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'appstore_bundle_inventorybundle_warehouse';
    }
}
