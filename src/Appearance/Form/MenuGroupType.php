<?php

namespace App\Appearance\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Tool\Entity\Syndicate;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Appearance\Entity\MenuGroup;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class MenuGroupType extends AbstractType
{
        /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name',TextType::class, ['attr'=>['class'=>'form-control input-sm','placeholder'=>'Enter menu group name'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required'),
                    new Length(max: 200)
                ]
            ])
            ->add('groupType',TextType::class, ['attr'=>['class'=>'form-control input-sm','placeholder'=>'Enter menu group name'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required'),
                    new Length(max: 200)
                ]
            ])
            ->add('syndicates', EntityType::class, [
                'required'      => true,
                'expanded'      =>true,
                'multiple'      =>true,
                'class' => Syndicate::class,
                'choice_label' => 'name',
                'attr'=>['class'=>''],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('s')
                    ->andWhere("s.status = 1")
                    ->andWhere("s.level = 1")
                    ->orderBy('s.name','ASC'),
            ])
            ->add('groupType', ChoiceType::class, [
                'choices' => ['Website' => 'Website', 'Mobile' => 'Mobile'],
                'expanded'=>true,
                'multiple'=>true,

            ]);
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MenuGroup::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'setting_bundle_appearancebundle_menugroup';
    }
}
