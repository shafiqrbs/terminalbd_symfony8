<?php

namespace App\Hospital\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Accounting\Entity\AccountHead;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use App\User\Entity\User;
use App\Hospital\Entity\HmsCategory;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Hospital\Entity\Particular;
use App\Hospital\Repository\HmsCategoryRepository;
use Doctrine\ORM\EntityRepository;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class PathologyType extends AbstractType
{


    public function __construct(private readonly HmsCategoryRepository $emCategory, private readonly GlobalOption $globalOption)
    {
    }


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            ->add('name',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter test name'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required'),
                ]
            ])
            ->add('accountHead', EntityType::class, [
                'class'     => AccountHead::class,
                'group_by'  => 'parent.name',
                'property'  => 'name',
                'empty_value' => '---Choose a account---',
                'attr'=>['class'=>'span12 m-wrap select2'],
                'choice_translation_domain' => true,
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->join("e.parent",'c')
                    ->where("e.status = 1")
                    ->andWhere("c.isParent =1")
                    ->orderBy("e.name", "ASC")
            ])
            ->add('sepcimen',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter sample collection']])
            ->add('testDuration')
            ->add('reportFormat')
            ->add('reportUnitHide')
            ->add('discountValid')
            ->add('isAttachment')
            ->add('additionalField')
            ->add('isReportContent')
            ->add('isMachineFormat')
            ->add('reportMachineName',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter report machine name']])
            ->add('room',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter room/cabin name or no']])
            ->add('instruction',TextareaType::class, ['attr'=>['class'=>'m-wrap span12','rows'=>'3','placeholder'=>'Enter test related any instruction for patient']])
            ->add('reportContent',TextareaType::class, ['attr'=>['class'=>'big-editor ','rows'=>15,'placeholder'=>'Enter test related any instruction for patient']])
            ->add('minimumPrice',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter minimum price']])
            ->add('reportHeight',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter report height']])
            ->add('commission',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter commission']])
            ->add('overHeadCost',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter over head cost']])
            ->add('price',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter price'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required'),
                ]
            ])
            ->add('assignOperator', EntityType::class, [
                'required'    => false,
                'class' => User::class,
                'property' => 'userFullName',
                'attr'=>['class'=>'span12 select2'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.enabled = 1")
                    ->andWhere("e.globalOption =".$this->globalOption->getId())
             /*       ->join('e.profile','p')*/
             /*       ->join('p.designation','designation')*/
             /*       ->where("e.enabled = 1")*/
             /*       ->andWhere("designation.slug = 'operator'")*/
                    ->orderBy("e.id","ASC")
            ])

            ->add('department', EntityType::class, [
                'required'    => true,
                'empty_value' => '---Select department---',
                'attr'=>['class'=>'m-wrap span12 select2'],
                'class' => HmsCategory::class,
                'property' => 'nestedLabel',
                'choices'=> $this->DepartmentChoiceList()
            ])

            ->add('category', EntityType::class, [
                'required'    => true,
                'empty_value' => '---Select pathology---',
                'attr'=>['class'=>'m-wrap span12 select2'],
                'class' => HmsCategory::class,
                'property' => 'nestedLabel',
                'choices'=> $this->PathologyChoiceList()
            ]);
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
        return 'appstore_bundle_hospitalbundle_particular';
    }

    /**
     * @return mixed
     */
    protected function PathologyChoiceList()
    {
        return $this->emCategory->getParentCategoryTree($parent = 2 /** Pathology */ );

    }
    /**
     * @return mixed
     */
    protected function DepartmentChoiceList()
    {
        return $this->emCategory->getParentCategoryTree($parent = 7 /** Department */);

    }

}
