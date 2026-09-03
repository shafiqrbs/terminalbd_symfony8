<?php

namespace App\Hospital\Form;


use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Hospital\Entity\Particular;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Hospital\Entity\InvoiceParticular;
use App\Hospital\Entity\HospitalConfig;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;


class InvoiceParticularType extends AbstractType
{

    public function __construct(private readonly HospitalConfig $hospitalConfig)
    {
    }




    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            ->add('assignDoctor', EntityType::class, [
                'required'    => false,
                'class' => Particular::class,
                'property' => 'referred',
                'attr'=>['class'=>'span12 select2'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where('e.hospitalConfig ='.$this->hospitalConfig->getId())
                    ->andWhere("e.service = 5")
                    ->andWhere("e.status = 1")
                    ->orderBy("e.name","ASC")
            ])
            ->add('process', ChoiceType::class, [
                'attr'=>['class'=>'span12 select-custom'],
                'expanded'      =>false,
                'multiple'      =>false,
                'choices' => ['In-progress' => 'In-progress','Done' => 'Done', 'Damage' => 'Damage', 'Impossible' => 'Impossible'],
            ])
            ->add('comment',TextareaType::class, ['attr'=>['class'=>'m-wrap span12','rows'=>3,'placeholder'=>'Add any comment','autocomplete'=>'off']])
        ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => InvoiceParticular::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'appstore_bundle_hospitalbundle_invoice_particular';
    }



}
