<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use League\OAuth2\Client\Provider\GoogleUser;

class UserService
{
    public function __construct(
        private readonly UserRepository $userRepository,
    )
    {
    }

    public function createUser(GoogleUser $googleUser): User
    {
        $user = new User();
        $user->setGoogleId($googleUser->getId());
        $user->setEmail($googleUser->getEmail());
        $this->userRepository->persist($user, true);

        return $user;
    }
}