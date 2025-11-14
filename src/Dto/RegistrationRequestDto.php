<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class RegistrationRequestDto
{
    #[Assert\NotBlank(message: "Email nie może być pusty.")]
    #[Assert\Email(message: "Podany email jest nieprawidłowy.")]
    public string $email;

    #[Assert\NotBlank(message: "Hasło nie może być puste.")]
    #[Assert\Length(min: 8, minMessage: "Hasło musi mieć co najmniej {{ limit }} znaków.")]
    #[Assert\Regex(
        pattern: "/\d/",
        message: "Hasło musi zawierać co najmniej jedną cyfrę."
    )]
    public string $password;
}

