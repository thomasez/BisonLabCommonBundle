<?php

namespace BisonLab\CommonBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username', 'Symfony\Component\Form\Extension\Core\Type\TextType', array('required' => true))
            ->add('email', 'Symfony\Component\Form\Extension\Core\Type\EmailType', array('required' => false))
            ->add('enabled', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array('required' => false))
            ->add('password', 'Symfony\Component\Form\Extension\Core\Type\PasswordType', array('required' => false))
            ->add('locked', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array('required' => false))
            ->add('roles', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', array('multiple' =>  true, 'choices' => 
                    array(
                        'ROLE_USER'    => 'Read Only - user', 
                        'ROLE_USER_RW' => 'User with read/write',
                        'ROLE_ADMIN'   => 'Admin',
                        'ROLE_SUPER_ADMIN' => 'Superadmin')
                        ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'BisonLab\CommonBundle\Entity\User'
        ));
    }
}
