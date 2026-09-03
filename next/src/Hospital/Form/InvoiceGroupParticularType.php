<?php

namespace App\Hospital\Form;


use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use App\Hospital\Entity\HmsCategory;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Hospital\Entity\InvoicePathologicalGroup;
use App\Hospital\Entity\HospitalConfig;
use App\Hospital\Entity\Particular;
use App\Hospital\Repository\HmsCategoryRepository;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;


class InvoiceGroupParticularType extends AbstractType
{

    public function __construct(private readonly HospitalConfig $hospitalConfig, private readonly HmsCategoryRepository $emCategory)
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
                'property' => 'reportDoctor',
                'empty_value' => '---Select Assign Doctor---',
                'attr'=>['class'=>'span12 select2'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where('e.hospitalConfig ='.$this->hospitalConfig->getId())
                    ->andWhere("e.service = 5")
                    ->andWhere("e.status = 1")
                    ->orderBy("e.name","ASC")
            ])
            ->add('assignLabuser', EntityType::class, [
                'required'    => false,
                'class' => Particular::class,
                'property' => 'reportDoctor',
                'empty_value' => '---Select Assign Lab User---',
                'attr'=>['class'=>'span12 select2'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where('e.hospitalConfig ='.$this->hospitalConfig->getId())
                    ->andWhere("e.service = 9")
                    ->andWhere("e.status = 1")
                    ->orderBy("e.name","ASC")
            ])
            ->add('process', ChoiceType::class, [
                'attr'=>['class'=>'span12 select-custom'],
                'expanded'      =>false,
                'multiple'      =>false,
                'choices' => ['Done' => 'Done', 'In-progress' => 'In-progress','Damage' => 'Damage', 'Impossible' => 'Impossible'],
            ])
            ->add('comment',TextareaType::class, ['attr'=>['class'=>'m-wrap span12','rows'=>3,'placeholder'=>'Add any comment','autocomplete'=>'off']])
            ->add('category', EntityType::class, [
                'required'    => true,
                'empty_value' => '---Select pathology---',
                'attr'=>['class'=>'m-wrap span12 select2'],
                'class' => HmsCategory::class,
                'property' => 'nestedLabel',
                'choices'=> $this->PathologyChoiceList()
            ])
        ;

    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => InvoicePathologicalGroup::class
        ]);
    }

    /**
     * @return mixed
     */
    protected function PathologyChoiceList()
    {
        return $this->emCategory->getParentCategoryTree($parent = 2 /** Pathology */ );

    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'invoiceParticular';
    }

}
