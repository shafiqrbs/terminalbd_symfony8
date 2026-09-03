<?php

namespace App\Hospital\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Hospital\Entity\PathologicalReport;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Hospital\Entity\Particular;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class PathologicalReportType extends AbstractType
{

    public function __construct(private readonly Particular $particular)
    {
    }


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            ->add('name',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter particular name'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required'),
                ]
            ])
            ->add('parent', EntityType::class, [
                'required'    => false,
                'class' => PathologicalReport::class,
                'empty_value' => '---Select parent name---',
                'property' => 'name',
                'attr'=>['class'=>'select2 span12'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('o')
                    ->where("o.status = 1")
                    ->andWhere("o.particular =".$this->particular->getId())
                    ->orderBy('o.name','ASC'),
            ])
            ->add('sampleValue',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter sample value']])
            ->add('referenceValue',TextareaType::class, ['attr'=>['class'=>'m-wrap span12','rows' => 3,'placeholder'=>'Enter reference value']])
            ->add('unit',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter unit name']]);
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PathologicalReport::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'appstore_bundle_hospitalbundle_pathological_report';
    }

}
