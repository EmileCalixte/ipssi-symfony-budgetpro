<?php


namespace App\Provider;


use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class UserProvider
{
    private $repository;
    private $em;

    public function __construct(UserRepository $repository, EntityManagerInterface $em)
    {
        $this->repository = $repository;
        $this->em = $em;
    }

    public function getUserByEmail(?string $email): ?User
    {
        if(is_null($email)) return null;
        return $this->repository->findOneBy(['email' => $email]);
    }

    public function getUserByApiKey(?string $apiKey): ?User
    {
        if(is_null($apiKey)) return null;
        return $this->repository->findOneBy(['apiKey' => $apiKey]);
    }

}