<?php

namespace App\Inventory\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Inventory\Entity\ExcelImporter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class ExcelImporterType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Add  file name'],
                'constraints' =>[
                    new NotBlank(message: 'Please choose  any valid file which extension excel')
                ]])
            ->add('mode', ChoiceType::class, [
                'required'    => false,
                'attr'=>['class'=>'span12'],
                'placeholder' => '---Choose a Mode---',
                'choices' => ['Stock' => 'stock', 'Purchase' => 'purchase'],
            ])
            ->add('file');
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ExcelImporter::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'excelimporter';
    }
}
