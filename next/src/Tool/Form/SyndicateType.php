<?php

namespace App\Tool\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Tool\Entity\Syndicate;
use Doctrine\ORM\EntityRepository;
use App\Tool\Repository\SyndicateRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class SyndicateType extends AbstractType
{

    public function __construct(private readonly SyndicateRepository $em)
    {
    }


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter name'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required'),
                    new Length(max: 200)
                ]
            ])
            ->add('entityName',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter name'],
                'constraints' =>[
                    new Length(max: 200)
                ]
            ])

            ->add('domainProperty', ChoiceType::class, [
                'attr'=>['class'=>' m-wrap span10 selectbox'],
                'empty_value' => '--- Select domain property ---',
                'choices' => [
                    'application'      => 'Application',
                    'businesses'      => 'Businesses',
                    'education'     => 'Education',
                    'ecommerce'     => 'Ecommerce',
                    'food'          => 'Food',
                    'medical'       => 'Medical',
                    'property'      => 'Property',
                    'reservation'   => 'Reservation',
                    'service'       => 'Service',
                    'transport'     => 'Transport',
                    'others'         => 'Others'
                ],
            ])


     /*       ->add('parent', 'entity', array(
                'required'    => true,
                'empty_value' => '--- Select parent ---',
                'attr'=>array('class'=>'location m-wrap span10 selectbox'),
                'class' => \App\Tool\Entity\Syndicate::class,
                'property' => 'nestedLabel',
                'choices'=> $this->SyndicateChoiceList()
            ))*/

            ->add('parent', EntityType::class, [
                'required'      => false,
                'empty_value' => '--- Select parent sector ---',
                'multiple'      =>false,
                'expanded'      =>false,
                'class'         => \App\Tool\Entity\Syndicate::class,
                'property'      => 'name',
                'attr'          =>['class'=>'m-wrap span12'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('d')
                    ->where("d.level = 1")
                    ->orderBy('d.name','ASC')
            ])

            ->add('status')
        ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Syndicate::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'setting_bundle_toolbundle_syndicate';
    }

    /**
     * @return mixed
     */
    protected function SyndicateChoiceList()
    {
        return $locationTree = $this->em->getFlatSyndicateTree();

    }

}
