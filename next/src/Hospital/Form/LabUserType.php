<?php

namespace App\Hospital\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Hospital\Entity\Particular;
use App\Hospital\Repository\HmsCategoryRepository;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class LabUserType extends AbstractType
{
    protected $emCategory;
    protected $globalOption;


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->emCategory = $options['emCategory'];
        $this->globalOption = $options['globalOption'];

        $builder

            ->add('name',TextType::class, ['attr'=>['class'=>'m-wrap span12','autocomplete'=>'off','placeholder'=>'Enter lab user name'],
                    'constraints' =>[new NotBlank(message: 'Please enter lab user name')]]
            )
            ->add('signatureFile')
            ->add('mobile',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter mobile no']])
            ->add('designation',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter designation']])
            ->add('doctorSignature',TextareaType::class, ['attr'=>['class'=>'m-wrap span12','rows'=>3,'placeholder'=>'Enter signature']])
        ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'emCategory' => null,
            'globalOption' => null,
        ]);

        $resolver->setDefaults([
            'data_class' => Particular::class,
            'error_bubbling'     => false,
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
