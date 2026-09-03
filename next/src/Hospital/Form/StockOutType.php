<?php

namespace App\Hospital\Form;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Hospital\Entity\HmsStockOut;
use App\Hospital\Entity\HospitalConfig;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class StockOutType extends AbstractType
{
    /** @var  HospitalConfig */

    public  $config;

    public function __construct(HospitalConfig $config)
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

	        ->add('invoiceFor', ChoiceType::class, [
		        'attr'=>['class'=>'span12 select-custom'],
		        'expanded'      =>false,
		        'multiple'      =>false,
		        'choices' => [
			        'expenditure' => 'Expenditure',
			        'customer' => 'Customer',
		        ],
	        ])
	        ->add('remark',TextareaType::class, ['attr'=>['class'=>'m-wrap span12 resize ','rows'=>3,'required' => false ,'label' => 'form.name','placeholder'=>'Enter remark']])
            ->add('payment',TextType::class, ['attr'=>['class'=>'numeric m-wrap span12','placeholder'=>'Payment amount']
            ]);
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => HmsStockOut::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'stockout';
    }
}
