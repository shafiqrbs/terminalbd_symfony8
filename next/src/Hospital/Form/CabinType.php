<?php

namespace App\Hospital\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Hospital\Entity\HmsServiceGroup;
use App\Accounting\Entity\AccountHead;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Hospital\Entity\Particular;
use Doctrine\ORM\EntityRepository;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class CabinType extends AbstractType
{
    protected $globalOption;


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->globalOption = $options['globalOption'];

        $builder

            ->add('serviceGroup', EntityType::class, [
                'required'    => false,
                'class' => HmsServiceGroup::class,
                'choice_label' => 'name',
                'attr'=>['class'=>'span12'],
                'placeholder' => '---Select cabin/ward ---',
                'constraints' =>[
                    new NotBlank(message: 'Please select required')
                ],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('b')
                    ->where("b.status = 1")
                    ->andWhere("b.service = 2")
                    ->andWhere("b.hospitalConfig =".$this->globalOption->getHospitalConfig()->getId())
                    ->orderBy("b.name", "ASC")
            ])
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
            ->add('price',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter price'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required'),
                ]
            ])
        ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'globalOption' => null,
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
        return 'particular';
    }

}
