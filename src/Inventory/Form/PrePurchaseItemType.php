<?php

namespace App\Inventory\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Inventory\Entity\Product;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Inventory\Entity\PrePurchaseItem;
use App\Inventory\Entity\InventoryConfig;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class PrePurchaseItemType extends AbstractType
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

            ->add('masterItem', EntityType::class, [
                'required'    => true,
                'class' => Product::class,
                'placeholder' => '---Choose a item ---',
                'choice_label' => 'name',
                'attr'=>['class'=>'span12 select2 itemInput'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required')
                ],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('p')
                    ->where("p.status = 1")
                    ->andWhere("p.inventoryConfig =".$this->inventoryConfig->getId())
                    ->orderBy("p.name","ASC"),
            ])

            ->add('requestQnt',TextType::class, ['attr'=>['class'=>'itemInput m-wrap span8','placeholder'=>'Item quantity'],
                'constraints' =>[
                    new NotBlank(message: 'Add item quantity')]])

            ->add('requestBy',TextType::class, ['attr'=>['class'=>'itemInput m-wrap span12','placeholder'=>'Item sales price'],
                'constraints' =>[
                    new NotBlank(message: 'Add sales price')]])

            ->add('availableDate', DateType::class, [
                'widget' => 'single_text',
                'placeholder' => [
                    'mm' => 'mm', 'dd' => 'dd','YY' => 'YY'

                ],
                'format' => 'dd-MM-yyyy',
                'attr' => ['class'=>'m-wrap span12 datePicker purchaseInput'],
                'view_timezone' => 'Asia/Dhaka'])
            ->add('content',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter remark']])
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
            'data_class' => PrePurchaseItem::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'prePurchaseItem';
    }
}
