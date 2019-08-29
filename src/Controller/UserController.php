<?php

namespace App\Controller;

use App\Entity\User;
use App\Provider\UserProvider;
use App\Repository\SubscriptionRepository;
use App\Repository\UserRepository;
use App\Utils\Utils;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserController extends AbstractFOSRestController
{
    private $userRepository;
    private $em;

    static private $postUserRequiredAttributes = [
        // 'subscriptionId' => Vérifié autrement car il n'y a pas directement de setter par ID
        'firstname' => 'setFirstname',
        'lastname' => 'setLastname',
        'email' => 'setEmail',
        'address' => 'setAddress',
        'country' => 'setCountry',
    ];

    static private $patchAdminUserModifiableAttributes = [
        // 'subscriptionId' => Vérifié autrement car il n'y a pas directement de setter par ID
        'firstname' => 'setFirstname',
        'lastname' => 'setLastname',
        'email' => 'setEmail',
        'apiKey' => 'setApiKey',
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
     * @SWG\Response(
     *     response="200",
     *     description="Returns list of users"
     * )
     */
    public function getApiUsers()
    {
        $users = $this->userRepository->findAll();
        return $this->view($users);
    }

    /**
     * @Rest\Get("/api/users/{id}")
     * @Rest\View(serializerGroups={"user"})
     * @SWG\Parameter(
     *     name="id",
     *     in="path",
     *     type="number",
     *     description="The ID of the user",
     *     required=true
     * )
     * @SWG\Response(
     *     response="200",
     *     description="Returns details of the user"
     * )
     * @SWG\Response(
     *     response="404",
     *     description="User not found"
     * )
     */
    public function getApiUser(User $user)
    {
        return $this->view($user);
    }

    /**
     * @Rest\Post("/api/users")
     * @Rest\View(serializerGroups={"user"})
     * @SWG\Parameter(
     *     name="firstname",
     *     in="body",
     *     type="string",
     *     description="The firstname of the user",
     *     required=true,
     *     @SWG\Schema(
     *          type="string",
     *          maxLength=255
     *     )
     * )
     * @SWG\Parameter(
     *     name="lastname",
     *     in="body",
     *     type="string",
     *     description="The lastname of the user",
     *     required=true,
     *     @SWG\Schema(
     *         type="string",
     *          maxLength=255
     *     )
     * )
     * @SWG\Parameter(
     *     name="email",
     *     in="body",
     *     type="string",
     *     description="The email of the user",
     *     required=true,
     *     @SWG\Schema(
     *          type="string",
     *          maxLength=255
     *     )
     * )
     * @SWG\Parameter(
     *     name="subscriptionId",
     *     in="body",
     *     type="number",
     *     description="The ID of the subscription for the user",
     *     required=true,
     *     @SWG\Schema(
     *          type="number"
     *     )
     * )
     * @SWG\Parameter(
     *     name="address",
     *     in="body",
     *     type="string",
     *     description="The address of the user",
     *     @SWG\Schema(
     *          type="string",
     *          maxLength=255
     *     )
     * )
     * @SWG\Parameter(
     *     name="country",
     *     in="body",
     *     type="string",
     *     description="The country of the user",
     *     @SWG\Schema(
     *          type="number",
     *          maxLength=255
     *     )
     * )
     * @SWG\Response(
     *     response="201",
     *     description="User created"
     * )
     * @SWG\Response(
     *     response="400",
     *     description="Malformed request body"
     * )
     */
    public function postApiUser(ValidatorInterface $validator, Request $request, SubscriptionRepository $subscriptionRepository, UserProvider $userProvider)
    {
        $user = new User();

        $user->setApiKey(Utils::generateApiKey());
        $user->setCreatedAt(new \DateTimeImmutable());
        $user->setRoles(['ROLE_USER']);

        foreach(static::$postUserRequiredAttributes as $attribute => $setter) {
            if(is_null($request->get($attribute))) {
                continue;
            }
            $user->$setter($request->get($attribute));
        }

        // Vérification manuelle de l'ID de la subscription
        if(!is_null($request->get('subscriptionId'))) {
            $subscription = $subscriptionRepository->find($request->get('subscriptionId'));
            if(!is_null($subscription)) {
                $user->setSubscription($subscription);
            }
        }

        $errors = [];

        $validationErrors = $validator->validate($user);

        /** @var ConstraintViolation $constraintViolation */
        foreach($validationErrors as $constraintViolation) {
            $message = $constraintViolation->getMessage();
            $propertyPath = $constraintViolation->getPropertyPath();
            if($propertyPath === 'subscription') $propertyPath = 'subscriptionId';
            $errors[] = ['property' => $propertyPath, 'message' => $message];
        }

        // Vérification que l'email n'est pas déjà utilisé
        $existingUser = $userProvider->getUserByEmail($user->getEmail());
        if(!is_null($existingUser) && $existingUser->getId() !== $user->getId()) {
            $errors[] = ['property' => 'email', 'message' => 'This email is already used'];
        }

        if(!empty($errors)) {
            return new JsonResponse($errors, 400);
        }

        $this->em->persist($user);
        $this->em->flush();
        return $this->view($user, 201);
    }

    /**
     * @Rest\Get("/api/admin/users")
     * @Rest\View(serializerGroups={"profile"})
     * @SWG\Response(
     *     response="200",
     *     description="Returns the list of users"
     * )
     * @SWG\Response(
     *     response="403",
     *     description="You don't have permission to perform this action"
     * )
     */
    public function getApiAdminUsers()
    {
        $users = $this->userRepository->findAll();
        return $this->view($users);
    }

    /**
     * @Rest\Get("/api/admin/users/{id}")
     * @Rest\View(serializerGroups={"profile"})
     * @SWG\Parameter(
     *     name="id",
     *     in="path",
     *     type="number",
     *     description="The ID of the user",
     *     required=true
     * )
     * @SWG\Response(
     *     response="200",
     *     description="Returns details of the user"
     * )
     * @SWG\Response(
     *     response="403",
     *     description="You don't have permission to perform this action"
     * )
     * @SWG\Response(
     *     response="404",
     *     description="User not found"
     * )
     */
    public function getApiAdminUser(User $user)
    {
        return $this->view($user);
    }

    /**
     * @Rest\Patch("/api/admin/users/{id}")
     * @Rest\View(serializerGroups={"profile"})
     * @SWG\Parameter(
     *     name="id",
     *     in="path",
     *     type="number",
     *     description="The ID of the user",
     *     required=true
     * )
     * @SWG\Parameter(
     *     name="firstname",
     *     in="body",
     *     type="string",
     *     description="The firstname of the user",
     *     @SWG\Schema(
     *          type="string",
     *          maxLength=255
     *     )
     * )
     * @SWG\Parameter(
     *     name="lastname",
     *     in="body",
     *     type="string",
     *     description="The lastname of the user",
     *     @SWG\Schema(
     *         type="string",
     *          maxLength=255
     *     )
     * )
     * @SWG\Parameter(
     *     name="email",
     *     in="body",
     *     type="string",
     *     description="The email of the user",
     *     @SWG\Schema(
     *          type="string",
     *          maxLength=255
     *     )
     * )
     * @SWG\Parameter(
     *     name="subscriptionId",
     *     in="body",
     *     type="number",
     *     description="The ID of the subscription for the user",
     *     @SWG\Schema(
     *          type="number"
     *     )
     * )
     * @SWG\Parameter(
     *     name="address",
     *     in="body",
     *     type="string",
     *     description="The address of the user",
     *     @SWG\Schema(
     *          type="string",
     *          maxLength=255
     *     )
     * )
     * @SWG\Parameter(
     *     name="country",
     *     in="body",
     *     type="string",
     *     description="The country of the user",
     *     @SWG\Schema(
     *          type="number",
     *          maxLength=255
     *     )
     * )
     * @SWG\Parameter(
     *     name="apiKey",
     *     in="body",
     *     type="string",
     *     description="The apiKey of the user",
     *     @SWG\Schema(
     *          type="number",
     *          maxLength=255
     *     )
     * )
     * @SWG\Response(
     *     response="200",
     *     description="User edited"
     * )
     * @SWG\Response(
     *     response="400",
     *     description="Malformed request body"
     * )
     * @SWG\Response(
     *     response="403",
     *     description="You don't have permission to perform this action"
     * )
     * @SWG\Response(
     *     response="404",
     *     description="User not found"
     * )
     */
    public function patchApiAdminUser(User $user, ValidatorInterface $validator, Request $request, SubscriptionRepository $subscriptionRepository, UserProvider $userProvider)
    {
        foreach(static::$patchAdminUserModifiableAttributes as $attribute => $setter) {
            if(is_null($request->get($attribute))) {
                continue;
            }
            $user->$setter($request->get($attribute));
        }

        // Vérification manuelle de l'ID de la subscription
        if(!is_null($request->get('subscriptionId'))) {
            $subscription = $subscriptionRepository->find($request->get('subscriptionId'));
            $user->setSubscription($subscription);
        }

        $errors = [];

        $validationErrors = $validator->validate($user);

        /** @var ConstraintViolation $constraintViolation */
        foreach($validationErrors as $constraintViolation) {
            $message = $constraintViolation->getMessage();
            $propertyPath = $constraintViolation->getPropertyPath();
            if($propertyPath === 'subscription') $propertyPath = 'subscriptionId';
            $errors[] = ['property' => $propertyPath, 'message' => $message];
        }

        // Vérification que l'email n'est pas déjà utilisé
        $existingUser = $userProvider->getUserByEmail($user->getEmail());
        if(!is_null($existingUser) && $existingUser->getId() !== $user->getId()) {
            $errors[] = ['property' => 'email', 'message' => 'This email is already used'];
        }

        // Vérification que l'apiKey n'est pas déjà utilisé
        $existingUser = $userProvider->getUserByApiKey($user->getApiKey());
        if(!is_null($existingUser) && $existingUser->getId() !== $user->getId()) {
            $errors[] = ['property' => 'apiKey', 'message' => 'This apiKey is already used'];
        }

        if(!empty($errors)) {
            return new JsonResponse($errors, 400);
        }

        $this->em->persist($user);
        $this->em->flush();
        return $this->view($user);
    }

    /**
     * @Rest\Delete("/api/admin/users/{id}")
     * @SWG\Parameter(
     *     name="id",
     *     in="path",
     *     type="number",
     *     description="The ID of the user",
     *     required=true
     * )
     * @SWG\Response(
     *     response="204",
     *     description="User deleted"
     * )
     * @SWG\Response(
     *     response="403",
     *     description="You don't have permission to perform this action"
     * )
     * @SWG\Response(
     *     response="404",
     *     description="User not found"
     * )
     */
    public function deleteApiAdminUser(User $user)
    {
        foreach($user->getCards() as $card) {
            $this->em->remove($card);
        }

        $this->em->remove($user);
        $this->em->flush();
        return new Response(null, 204);
    }
}