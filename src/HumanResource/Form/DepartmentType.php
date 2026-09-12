<?php

namespace App\HumanResource\Form;


use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\HumanResource\Entity\HrDepartment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class DepartmentType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $builder

            ->add('name',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter department name'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required'),
                ]
            ])
            ->add('status')
        ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => HrDepartment::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'setting';
    }
}
