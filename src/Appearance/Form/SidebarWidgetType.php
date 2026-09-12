<?php

namespace App\Appearance\Form;

use App\Product\Entity\Category;
use App\Product\Entity\CategoryRepository;
use App\Tool\Entity\GlobalOption;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class SidebarWidgetType extends AbstractType
{

    private $globalOption;

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->globalOption = $options['globalOption'];

        $builder->add('sidebarWidgetPanels', SidebarWidgetPanelType::class, ['globalOption' => $this->globalOption]);
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'globalOption' => null,
        ]);

        $resolver->setDefaults(array(
            'data_class' => 'App\Appearance\Entity\SidebarWidget'
        ));
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'setting_bundle_appearancebundle_featurewidget';
    }

}
