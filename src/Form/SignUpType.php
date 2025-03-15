<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class SignUpType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'Name cannot be blank',
                    ]),
                    new Length([
                        'min' => 2,
                        'max' => 35,
                        'minMessage' => 'Name must be at least {{ limit }} characters long',
                        'maxMessage' => 'Name cannot be longer than {{ limit }} characters',
                    ]),
                ],
            ])
            ->add('email', EmailType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'Email cannot be blank',
                    ]),
                    new Email([
                        'message' => 'Please enter a valid email address',
                    ]),
                ],
            ])
            ->add('statement', TextareaType::class, [
                'label' => 'short statement',
                'placeholder'=>'As a minimal precaution against trolls and bots, please provide a sentence or two (max 150 characters) telling us where you live and what motivates you to join.',
                'constraints' => [
                    new NotBlank([
                        'message' => 'This statement cannot be blank',
                    ]),
                    new Length([
                        'min' => 60,
                        'max' => 150,
                        'minMessage' => 'Statement must be at least {{ limit }} characters long',
                        'maxMessage' => 'Statement cannot be longer than {{ limit }} characters',
                    ]),
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Submit',
            ]);
    }
}
