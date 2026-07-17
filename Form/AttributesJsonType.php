<?php

namespace BisonLab\CommonBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Norzechowicz\AceEditorBundle\Form\Extension\AceEditor\Type\AceEditorType;
use Norzechowicz\AceEditorBundle\Form\Extension\JsonEditor\Type\JsonEditorType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class AttributesJsonType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $attributes_json = $options['data']['attributes_json'] ?? "{}";
        $builder
            ->add('attributes_json', JsonEditorType::class, array(
                'label' => 'Attributes, in Json',
                'data' => $attributes_json,
                'required' => true,
                'mode' => 'code',
                'width' => '100%',
                'height' => '1000px',
            ))
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'attributes_json_form';
    }
}
