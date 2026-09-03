<?php

namespace App\Restaurant\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Restaurant\Entity\Service;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Restaurant\Entity\Particular;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class ParticularType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            ->add('name',TextType::class, ['attr'=>['class'=>'m-wrap span12','autocomplete'=>'off','placeholder'=>'Enter service name'],
                'constraints' =>[
                    new NotBlank(message: 'Please enter particular name')
                ]]
            )

            ->add('service', EntityType::class, [
                'required'    => false,
                'class' => Service::class,
                'property' => 'name',
                'constraints' =>[
                    new NotBlank(message: 'Please select required')
                ],
                'empty_value' => '---Choose a particular type ---',
                'attr'=>['class'=>'span12 m-wrap'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                ->where("e.status = 1")
                ->andWhere('e.slug IN (:slugs)')
                ->setParameter('slugs',['token','value-added','invoice-mode','product-group'])
                ->orderBy("e.sorting","ASC")
            ])

        ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Particular::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'appstore_bundle_particular';
    }


}
