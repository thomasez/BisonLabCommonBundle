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
            ->add('username')
            ->add('email')
            ->add('enabled', 'checkbox', array('required' => false))
            ->add('password', 'password', array('required' => false))
            ->add('locked', 'checkbox', array('required' => false))
            ->add('roles', 'choice', array('multiple' =>  true, 'choices' => 
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

    public function getName()
    {
        return 'user';
    }
}
