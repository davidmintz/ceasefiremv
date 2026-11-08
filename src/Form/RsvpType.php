<?php

namespace App\Form;

use App\Enum\AttendanceMode;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Deliberately not bound to the Rsvp entity: the controller finds-or-creates
 * by email so a resubmission updates the existing row instead of failing on
 * the unique constraint (and without revealing that the email is already there).
 */
class RsvpType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('mode', EnumType::class, [
                'class' => AttendanceMode::class,
                'label' => 'I plan to attend',
                'expanded' => true,
                'choice_label' => fn (AttendanceMode $m) => $m->label(),
                'constraints' => [new Assert\NotNull(message: 'Please choose in person or Zoom')],
            ])
            ->add('name', TextType::class, [
                'label' => 'Name',
                'attr' => ['autocomplete' => 'name', 'maxlength' => 60],
                'constraints' => [
                    new Assert\NotBlank(message: 'Please enter your name'),
                    new Assert\Length(min: 2, max: 60),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => ['autocomplete' => 'email', 'maxlength' => 180],
                'constraints' => [
                    new Assert\NotBlank(message: 'Please enter your email address'),
                    new Assert\Email(message: 'Please enter a valid email address'),
                    new Assert\Length(max: 180),
                ],
            ])
            // honeypot: hidden from humans via CSS; bots tend to fill it in
            ->add('website', TextType::class, [
                'required' => false,
                'label' => 'Leave this field empty',
                'attr' => ['autocomplete' => 'off', 'tabindex' => '-1'],
                'row_attr' => ['class' => 'rsvp-hp', 'aria-hidden' => 'true'],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'RSVP',
                'attr' => ['class' => 'btn btn-primary'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_token_id' => 'rsvp',
        ]);
    }
}
