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

    public function __construct(GlobalOption $option)
    {
        $this->option = $option;

    }


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
                ->add('importMode', ChoiceType::class, [
                    'attr'=>['class'=>'m-wrap span12'],
                    'constraints' =>[
                        new NotBlank(message: 'Please choose  import mode')
                    ],
                    'expanded'      =>false,
                    'multiple'      =>false,
                    'choices' => [
                        'stock' => 'Stock Item',
                        'purchase' => 'Purchase',
                        'sales' => 'Sales',
                    ],
                ])
            ->add('file');
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
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
