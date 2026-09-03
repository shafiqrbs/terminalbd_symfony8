<?php

namespace App\Restaurant\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Restaurant\Entity\Particular;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Restaurant\Entity\RestaurantDamage;
use App\Restaurant\Entity\RestaurantConfig;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class ResturantDamageType extends AbstractType
{

	/** @var  $config RestaurantConfig */

	public  $config;

	public function __construct(RestaurantConfig  $config)
	{
		$this->config = $config;

	}


	/**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

	        ->add('particular', EntityType::class, [
		        'required'    => true,
		        'class' => Particular::class,
		        'empty_value' => '---Choose a product ---',
		        'property' => 'name',
		        'attr'=>['class'=>'span12 m-wrap select2'],
		        'constraints' =>[ new NotBlank(message: 'Please select your vendor name') ],
		        'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
			                  ->where("e.status = 1")
			                  ->andWhere("e.restaurantConfig =".$this->config->getId())
			                  ->andWhere("e.remainingQuantity > 0"),
	        ])
	        ->add('notes',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter notes ']])
	        ->add('quantity',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter quantity'],
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
            'data_class' => RestaurantDamage::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'damage';
    }


}
