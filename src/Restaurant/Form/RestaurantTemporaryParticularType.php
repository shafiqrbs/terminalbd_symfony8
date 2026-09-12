<?php

namespace App\Restaurant\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Restaurant\Entity\Particular;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Restaurant\Entity\RestaurantTemporary;
use App\Restaurant\Entity\RestaurantConfig;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class RestaurantTemporaryParticularType extends AbstractType
{
    protected $restaurantConfig;


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->restaurantConfig = $options['restaurantConfig'];

        $builder

            ->add('particular', EntityType::class, [
                'class'     => Particular::class,
                'group_by'  => 'category.name',
                'choice_label'  => 'codeName',
                'placeholder' => '--- Choose Code & Particular ---',
                'attr'=>['class'=>'span12 m-wrap select2 particular'],
                'choice_translation_domain' => true,
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->join("e.category",'c')
                    ->where("c.status=1")
                    ->andWhere("e.restaurantConfig=".$this->restaurantConfig->getId())
                    ->andWhere("e.status=1")
                    ->orderBy("c.sorting", "ASC")
                    ->addOrderBy("e.sorting", "ASC")
            ]);

    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'restaurantConfig' => null,
        ]);

        $resolver->setDefaults([
            'data_class' => RestaurantTemporary::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'restaurant_particular';
    }

}
