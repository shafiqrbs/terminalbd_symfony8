<?php

namespace App\Business\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Business\Entity\ItemImport;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class ItemImporterType extends AbstractType
{

    /** @var  GlobalOption */
    public  $option;

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->option = $options['option'];

        $builder
            ->add('name',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Add  file name'],
                'constraints' =>[
                    new NotBlank(message: 'Please choose  any valid file which extension excel')
                ]])
                ->add('importMode', ChoiceType::class, [
                    'attr'=>['class'=>'m-wrap span12'],
                    'constraints' =>[
                        new NotBlank(message: 'Please choose  import mode')
                    ],
                    'expanded'      =>false,
                    'multiple'      =>false,
                    'choices' => ['Stock Item' => 'stock', 'Purchase' => 'purchase', 'Sales' => 'sales'],
                ])
            ->add('file');
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'option' => null,
        ]);

        $resolver->setDefaults([
            'data_class' => ItemImport::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'importer';
    }
}
