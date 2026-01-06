<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use League\OAuth2\Client\Provider\GoogleUser;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;

class UserService
{
    public function __construct(
        private readonly UserRepository $userRepository,
    )
    {
    }

    public function createUser(ResourceOwnerInterface $resourceOwner): User
    {
        $user = new User();
        $user->setGoogleId($resourceOwner->getId());
        
        if ($resourceOwner instanceof GoogleUser) {
            $user->setEmail($resourceOwner->getEmail());
        }

        $this->userRepository->persist($user, true);

        return $user;
    }
}