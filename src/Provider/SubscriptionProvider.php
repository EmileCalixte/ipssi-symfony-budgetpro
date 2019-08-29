<?php


namespace App\Provider;


use App\Entity\Subscription;
use App\Repository\SubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;

class SubscriptionProvider
{
    private $repository;
    private $em;

    public function __construct(SubscriptionRepository $repository, EntityManagerInterface $em)
    {
        $this->repository = $repository;
        $this->em = $em;
    }

    public function getAllSubscriptionsIds(): array
    {
        $subscriptions = $this->repository->findAll();
        $ids = [];
        foreach($subscriptions as $subscription) {
            $ids[] = $subscription->getId();
        }
        return $ids;
    }

    public function getSubscriptionById($id): Subscription
    {
        return $this->repository->findOneBy(['id' => $id]);
    }
}