<?php


namespace App\Provider;


use App\Repository\CardRepository;
use Doctrine\ORM\EntityManagerInterface;

class CardProvider
{
    private $repository;
    private $em;

    public function __construct(CardRepository $repository, EntityManagerInterface $em)
    {
        $this->repository = $repository;
        $this->em = $em;
    }

    public function getCardByNumber(?string $cardNumber)
    {
        if(is_null($cardNumber)) return null;
        return $this->repository->findOneBy(['creditCardNumber' => $cardNumber]);
    }
}