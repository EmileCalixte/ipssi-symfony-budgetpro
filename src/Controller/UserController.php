<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\SubscriptionRepository;
use App\Repository\UserRepository;
use App\Utils\Utils;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserController extends AbstractFOSRestController
{
    private $userRepository;
    private $em;

    static private $postRequiredAttributes = [
        // 'subscriptionId' => Vérifié autrement car il n'y a pas directement de setter par ID
        'firstname' => 'setFirstname',
        'lastname' => 'setLastname',
        'email' => 'setEmail',
        'address' => 'setAddress',
        'country' => 'setCountry',
    ];

    public function __construct(UserRepository $userRepository, EntityManagerInterface $em)
    {
        $this->userRepository = $userRepository;
        $this->em = $em;
    }

    /**
     * @Rest\Get("/api/users")
     * @Rest\View(serializerGroups={"users"})
     */
    public function getApiUsers()
    {
        $users = $this->userRepository->findAll();
        return $this->view($users);
    }

    /**
     * @Rest\Get("/api/users/{id}")
     * @Rest\View(serializerGroups={"user"})
     */
    public function getApiUser(User $user)
    {
        return $this->view($user);
    }

    /**
     * @Rest\Post("/api/users")
     * @Rest\View(serializerGroups={"user"})
     */
    public function postApiUser(ValidatorInterface $validator, Request $request, SubscriptionRepository $subscriptionRepository)
    {
        $user = new User();

        $user->setApiKey(Utils::generateApiKey());
        $user->setCreatedAt(new \DateTimeImmutable());
        $user->setRoles(['ROLE_USER']);


        $errors = [];

        foreach(static::$postRequiredAttributes as $attribute => $setter) {
            if(is_null($request->get($attribute))) {
                continue;
            }
            $user->$setter($request->get($attribute));
        }

        if(!is_null($request->get('subscriptionId'))) {
            $subscription = $subscriptionRepository->find($request->get('subscriptionId'));
            if(!is_null($subscription)) {
                $user->setSubscription($subscription);
            }
        }

        $validationErrors = $validator->validate($user);

        /** @var ConstraintViolation $constraintViolation */
        foreach($validationErrors as $constraintViolation) {
            $message = $constraintViolation->getMessage();
            $propertyPath = $constraintViolation->getPropertyPath();
            if($propertyPath === 'subscription') $propertyPath = 'subscriptionId';
            $errors[] = [ 'property' => $propertyPath, 'message' => $message];
        }

        if(!empty($errors)) {
            return new JsonResponse($errors, 400);
        }

        $this->em->persist($user);
        $this->em->flush();
        return $this->view($user, 201);
    }
}