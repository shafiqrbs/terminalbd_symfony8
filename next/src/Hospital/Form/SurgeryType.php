<?php

namespace App\Hospital\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Accounting\Entity\AccountHead;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use App\Hospital\Entity\HmsServiceGroup;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Hospital\Entity\Particular;
use App\Hospital\Entity\HospitalConfig;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class SurgeryType extends AbstractType
{
    protected $config;


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->config = $options['config'];

        $builder

            ->add('name',TextType::class, ['attr'=>['class'=>'m-wrap span12','autocomplete'=>'off','placeholder'=>'Enter surgery name'],
                    'constraints' =>[
                        new NotBlank(message: 'Please enter cabin name')
                    ]]
            )
            ->add('accountHead', EntityType::class, [
                'class'     => AccountHead::class,
                'group_by'  => 'parent.name',
                'choice_label'  => 'name',
                'placeholder' => '---Choose a account---',
                'attr'=>['class'=>'span12 m-wrap select2'],
                'choice_translation_domain' => true,
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->join("e.parent",'c')
                    ->where("e.status = 1")
                    ->andWhere("c.isParent =1")
                    ->orderBy("e.name", "ASC")
            ])
            ->add('room',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter room/cabin name or no']])
            ->add('content',TextareaType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter content']])
            ->add('price',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter fees'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required'),
                ]
            ])
            ->add('surgeryDepartment', EntityType::class, [
                'required'    => true,
                'class' => HmsServiceGroup::class,
                'choice_label' => 'name',
                'placeholder' => '---Select surgery department---',
                'attr'=>['class'=>'span12 m-wrap select2'],
                'constraints' =>[
                    new NotBlank(message: 'Select surgery department'),
                ],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where('e.hospitalConfig ='.$this->config->getId())
                    ->andWhere("e.service = 10")
                    ->andWhere("e.status = 1")
                    ->orderBy("e.name","ASC")
            ])
            ->add('minimumPrice',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter minimum price']])
            ->add('commission',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter commission']])

        ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'config' => null,
        ]);

        $resolver->setDefaults([
            'data_class' => Particular::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'appstore_bundle_hospitalbundle_particular';
    }

}
