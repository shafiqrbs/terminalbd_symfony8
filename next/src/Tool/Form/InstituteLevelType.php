<?php

namespace App\Tool\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Tool\Repository\InstituteLevelRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class InstituteLevelType extends AbstractType
{


    public function __construct(private readonly InstituteLevelRepository $em)
    {
    }


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

            $builder
                ->add('name',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter name'],
                    'constraints' =>[
                        new NotBlank(message: 'Please input required'),
                    ]
                ])
                ->add('parent', EntityType::class, [
                    'required'    => true,
                    'empty_value' => '--- Select parent ---',
                    'attr'=>['class'=>'location m-wrap span10 selectbox'],
                    'class' => 'SettingToolBundle:InstituteLevel',
                    'property' => 'nestedLabel',
                    'choices'=> $this->instituteChoiceList()
                ])

                ->add('status')
            ;

    }

    /**
     * @return mixed
     */
    protected function instituteChoiceList()
    {
        return $locationTree = $this->em->getFlatInstituteLevelTree();

    }


    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => 'App\Tool\Entity\InstituteLevel'
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'setting_bundle_toolbundle_institutelevel';
    }
}
