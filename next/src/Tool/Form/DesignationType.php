<?php

namespace App\Tool\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Tool\Entity\Designation;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Tool\Repository\DesignationRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class DesignationType extends AbstractType
{
    protected $em;


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->em = $options['em'];

        $builder

            ->add('name',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter expense category name'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required'),

                ]
            ])
            ->add('parent', EntityType::class, [
                'required'    => true,
                'placeholder' => '---Select Parent Designation---',
                'attr'=>['class'=>'ExpenseCategory m-wrap span12 select2'],
                'class' => Designation::class,
                'choice_label' => 'nestedLabel',
                'choices'=> $this->DesignationChoiceList()
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
            'em' => null,
        ]);

        $resolver->setDefaults([
            'data_class' => Designation::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'setting_bundle_toolbundle_designation';
    }

    /**
     * @return mixed
     */
    protected function DesignationChoiceList()
    {
        return $this->em->getFlatDesignationTree();

    }

}
