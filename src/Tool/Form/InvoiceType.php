<?php

namespace App\Tool\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Tool\Entity\InvoiceModule;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class InvoiceType extends AbstractType
{


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            ->add('globalOption', EntityType::class, [
                    'required'    => false,
                    'class' => GlobalOption::class,
                    'placeholder' => '---Select Domain ---',
                    'choice_label' => 'name',
                    'attr'     =>['id' => '' , 'class' => 'm-wrap span12 select2'],
                    'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('s')
                        ->andWhere("s.status = 1")
                        ->orderBy('s.name','ASC'),
                ])
            ->add('name',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Client name']])
            ->add('billFor',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Client bill for'],
                'constraints' =>[
                    new NotBlank(message: 'Please bill for')
            ]])
            ->add('address',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Client address']])
            ->add('mobile',TextType::class, ['attr'=>['class'=>'m-wrap span12 mobile','placeholder'=>'Add mobile no']])
            ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => InvoiceModule::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'appstore_bundle_invoice_module';
    }

    /**
     * @return mixed
     */
    protected function categoryChoiceList()
    {

        return $categoryTree = $this->em->getCategoryOptions();

    }
}
