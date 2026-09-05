<?php

namespace App\Hospital\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Hospital\Entity\Particular;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Hospital\Entity\HmsStockOutItem;
use App\Hospital\Entity\HospitalConfig;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class StockOutItemType extends AbstractType
{
    /** @var  HospitalConfig */

    public  $config;

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->config = $options['config'];

        $builder

	        ->add('particular', EntityType::class, [
		        'required'    => false,
		        'choice_label' => 'particularNameCode',
		        'placeholder' => '--- Select medicine/accessories ---',
		        'attr'=>['class'=>'m-wrap span12 select2 particular'],
		        'class' => Particular::class,
		        'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
			            ->join("e.service",'s')
				        ->andWhere('s.slug IN (:ids)')
				        ->setParameter('ids', ['medicine','accessories'])
				        ->andWhere("e.hospitalConfig = {$this->config->getId()}")
				        ->orderBy("e.name","ASC")

	        ])
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
            'data_class' => HmsStockOutItem::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'stockoutitem';
    }
}
