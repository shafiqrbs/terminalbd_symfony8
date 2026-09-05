<?php

namespace App\Inventory\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Location\Entity\Country;
use App\Tool\Entity\Branding;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Inventory\Entity\Item;
use App\Inventory\Entity\InventoryConfig;
use App\Inventory\Repository\ItemTypeGroupingRepository;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class ItemWebType extends AbstractType
{

    /** @var  ItemTypeGroupingRepository */

    private $em;

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
            ->add('name',TextType::class, ['attr'=>['class'=>'m-wrap span12 ','placeholder'=>'Add web item name']])
            ->add('content',TextareaType::class, ['attr'=>['class'=>'no-resize span12','rows'=>5]])
            ->add('webPrice',TextType::class, ['attr'=>['class'=>'m-wrap span12 ','placeholder'=>'web price']])
            ->add('country', EntityType::class, [
                'required'      => true,
                'expanded'      =>false,
                'placeholder' => 'Choose made in',
                'class' => Country::class,
                'choice_label' => 'name',
                'attr'=>['class'=>'span12 select2'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('m')
                    ->orderBy('m.name','ASC'),
            ])
            ->add('brand', EntityType::class, [
                'required'    => false,
                'class' => Branding::class,
                'placeholder' => 'Choose a brand',
                'choice_label' => 'name',
                'attr'=>['class'=>'span12 select2'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('b')
                    ->Where("b.status = 1"),
            ])
            ->add('ageGroup', ChoiceType::class, [
                'attr'=>['class'=>'span12 select2'],
                'constraints' =>[
                    new NotBlank(message: 'Please chose required')
                ],
                'choices' => ['Kids' => 'Kids', 'Adult' => 'Adult'],
            ])

            ->add('gender', ChoiceType::class, [
                'attr'=>['class'=>'span12 select2'],
                'constraints' =>[
                    new NotBlank(message: 'Please chose required')
                ],
                'choices' => ['Male' => 'Male', 'Female' => 'Female'],
            ])
            ->add('material',TextType::class, ['attr'=>['class'=>'m-wrap span12 ']])
            ->add('isWeb')
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
            'data_class' => Item::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'appstore_bundle_inventorybundle_item';
    }

    /**
     * @return mixed
     */
    protected function ItemTypeChoiceList()
    {
        return $itemTypeTree = $this->em->getItemTypeTree();

    }
}
