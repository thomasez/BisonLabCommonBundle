<?php

namespace BisonLab\CommonBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;

use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username', TextType::class, array('required' => true))
            ->add('email', EmailType::class, array('required' => false))
            ->add('enabled', CheckboxType::class, array('required' => false))
            ->add('groups', EntityType::class,
                array(
                    'required' => true,
                    'multiple' => true,
                    // TODO: This may actually also be wrong. Gotta find the
                    // group class in a config or from the group manager.
                    'class' => 'BisonLabCommonBundle:Group'
                ))
            ->add('roles', ChoiceType::class,
                array(
                    'multiple' =>  true,
                    'choices' =>
                        array(
                            'Read Only - user' => 'ROLE_USER',
                            'User with read/write' => 'ROLE_USER_RW',
                            'Admin' => 'ROLE_ADMIN'
                            )
                )
            )
            ->add('submit', SubmitType::class, array('label' => "Save"))
            ->setMethod("POST")
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'BisonLab\CommonBundle\Entity\User'
        ));
    }
}
