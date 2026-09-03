<?php

namespace App\Medicine\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Medicine\Entity\MedicineMinimumStock;
use App\Medicine\Entity\MedicineConfig;
use App\Medicine\Repository\MedicineMinimumStockRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class MedicineMinimumStockType extends AbstractType
{

    /** @var $config MedicineConfig */
    private $config;



    public function __construct(/** @var $em MedicineMinimumStockRepository */
    private readonly MedicineMinimumStockRepository $em, MedicineConfig $config)
    {
        $this->config = $config->getId();

    }
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $builder
            ->add('minQuantity',TextType::class, ['attr'=>['class'=>'m-wrap span12 inputs ','required' => true ,'label' => 'form.name','placeholder'=>'Enter min qnt'],
                'constraints' =>[
                    new NotBlank(message: 'Please enter min qnt')
                ]])

            ->add('reorderQuantity',TextType::class, ['attr'=>['class'=>'m-wrap span12 inputs ','required' => true ,'label' => 'form.name','placeholder'=>'Enter min qnt'],
                ])

            ->add('company', ChoiceType::class, [
                'attr'=>['class'=>'m-wrap span12 select2'],
                'constraints' =>[
                    new NotBlank(message: 'Select company name')],
                'empty_value' => '---Choose a company---',
                'choices' => $this->companyChoiceList($this->config),
            ])
            ->add('medicineForm', ChoiceType::class, [
                'attr'=>['class'=>'m-wrap span12 select2'],
                'constraints' =>[
                    new NotBlank(message: 'Select Medicine Form')],
                'empty_value' => '---Choose a Medicine Form---',
                'choices' => $this->medicineFormList(),
            ]);
    }

    public function companyChoiceList($config)
    {
        return $syndicateTree = $this->em->companyList($config);

    }
    
     public function medicineFormList()
    {
        return $syndicateTree = $this->em->medicineFormList();

    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MedicineMinimumStock::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'medicineMinimumStock';
    }
}
