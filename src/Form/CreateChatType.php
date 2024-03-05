<?php

namespace App\Form;

use App\Entity\Chats;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class CreateChatType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('rolesAuth' ,ChoiceType::class, [
                'choices'  => [
                    'ROLE_USER' => 'User',
                    'ROLE_ADMIN' => 'Admin',
                ],
                'multiple' => true,
            ])
            ->add('description')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Chats::class,
        ]);
    }
}
