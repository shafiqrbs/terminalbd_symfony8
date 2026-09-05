<?php

namespace App\Business\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Business\Entity\BusinessParticular;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Business\Entity\BusinessDamage;
use App\Business\Entity\BusinessConfig;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class BusinessDamageType extends AbstractType
{

	/** @var  $config BusinessConfig */

	public  $config;

	/**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->config = $options['config'];

        $builder

	        ->add('businessParticular', EntityType::class, [
		        'required'    => true,
		        'class' => BusinessParticular::class,
		        'placeholder' => '---Choose a product ---',
		        'choice_label' => 'name',
		        'attr'=>['class'=>'span12 m-wrap select2'],
		        'constraints' =>[ new NotBlank(message: 'Please select your vendor name') ],
		        'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
			                  ->where("e.status = 1")
			                  ->andWhere("e.businessConfig =".$this->config->getId())
			                  ->andWhere("e.remainingQuantity > 0"),
	        ])
	        ->add('notes',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter notes ']])
	        ->add('quantity',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter product name'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required'),
                ]
            ]);
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
            'data_class' => BusinessDamage::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'businessParticular';
    }

}
