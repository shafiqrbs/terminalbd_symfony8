<?php

namespace App\Hospital\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Tool\Entity\ProductUnit;
use App\Medicine\Entity\MedicineParticular;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Medicine\Entity\MedicineStock;
use App\Medicine\Entity\MedicineConfig;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class MedicineStockType extends AbstractType
{
    protected $medicineConfig;


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->medicineConfig = $options['medicineConfig'];

        $builder
            ->add('name',TextType::class, ['attr'=>['class'=>'m-wrap span12 autoComplete2Medicine','placeholder'=>'Enter medicine,herbal,non-medicine,surgery & medical device etc'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required'),
                ]
            ])
            ->add('salesPrice',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'MRP price']])
            ->add('purchasePrice',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Purchase price']])
            ->add('minQuantity',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Minimum']])
            ->add('reorderQuantity',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Reorder quantity']])
            ->add('rackNo',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Rack no']])
            ->add('unit', EntityType::class, [
                'required'    => true,
                'class' => ProductUnit::class,
                'choice_label' => 'name',
                'placeholder' => '---Choose a unit ---',
                'attr'=>['class'=>'span12 select2'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('p')
                    ->where("p.status = 1")
                    ->orderBy("p.name","ASC"),
            ])
            ->add('rackNo', EntityType::class, [
                'required'    => false,
                'class' => MedicineParticular::class,
                'placeholder' => '---Choose a rack position ---',
                'choice_label' => 'name',
                'attr'=>['class'=>'m-wrap span12 inputs'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->join("e.particularType","pt")
                    ->where("e.status = 1")
                    ->andWhere("e.medicineConfig =". $this->medicineConfig->getId())
                    ->andWhere("pt.slug = 'rack'"),
            ])
            ->add('accessoriesBrand', EntityType::class, [
                'required'    => false,
                'group_by'  => 'particularType.name',
                'class' => MedicineParticular::class,
                'placeholder' => '---Select medicine & others brand ---',
                'choice_label' => 'name',
                'choice_translation_domain' => true,
                'attr'=>['class'=>'m-wrap span12 inputs'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->join("e.particularType","pt")
                    ->where("e.status = 1")
                    ->andWhere("pt.status = 1")
                    ->andWhere("e.medicineConfig =". $this->medicineConfig->getId())
                    ->andWhere('pt.modeFor = :brand')->setParameter('brand','brand'),
            ])
 /*           ->add('noDiscount')
            ->add('file')
 */       ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'medicineConfig' => null,
        ]);

        $resolver->setDefaults([
            'data_class' => MedicineStock::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'medicineStock';
    }
}
