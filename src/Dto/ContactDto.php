<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class ContactDto
{
    #[Assert\NotBlank(message: "Le nom est obligatoire.")]
    public string $name = '';

    #[Assert\NotBlank(message: "L'adresse e-mail est obligatoire.")]
    #[Assert\Email(message: "L'adresse e-mail n'est pas valide.")]
    public string $email = '';

    #[Assert\NotBlank(message: "Le message est obligatoire.")]
    #[Assert\Length(min: 10, minMessage: "Le message doit faire au moins 10 caractères.")]
    public string $message = '';
}
