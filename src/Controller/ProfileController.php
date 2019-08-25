<?php


namespace App\Controller;


use App\Entity\Card;
use App\Provider\CardProvider;
use App\Provider\UserProvider;
use App\Repository\SubscriptionRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProfileController extends AbstractFOSRestController
{
    private $userRepository;
    private $em;

    static private $patchProfileModifiableAttributes = [
        // 'subscriptionId' => Vérifié autrement car il n'y a pas directement de setter par ID
        'firstname' => 'setFirstname',
        'lastname' => 'setLastname',
        'address' => 'setAddress',
        'country' => 'setCountry',
    ];

    static private $postCardRequiredAttributes = [
        'name' => 'setName',
        'creditCardType' => 'setCreditCardType',
        'creditCardNumber' => 'setCreditCardNumber',
        'currencyCode' => 'setCurrencyCode',
        'value' => 'setValue',
    ];

    static private $patchCardModifiableAttributes = [
        'name' => 'setName',
        'creditCardType' => 'setCreditCardType',
        'creditCardNumber' => 'setCreditCardNumber',
        'currencyCode' => 'setCurrencyCode',
        'value' => 'setValue',
    ];

    public function __construct(UserRepository $userRepository, EntityManagerInterface $em)
    {
        $this->userRepository = $userRepository;
        $this->em = $em;
    }

    /**
     * @Rest\Get("/api/profile")
     * @Rest\View(serializerGroups={"profile"})
     */
    public function getApiProfile()
    {
        return $this->view($this->getUser());
    }

    /**
     * @Rest\Patch("/api/profile")
     * @Rest\View(serializerGroups={"profile"})
     */
    public function PatchApiProfile(ValidatorInterface $validator, Request $request, SubscriptionRepository $subscriptionRepository, UserProvider $userProvider)
    {
        $user = $this->getUser();

        foreach(static::$patchProfileModifiableAttributes as $attribute => $setter) {
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

        if(!empty($errors)) {
            return new JsonResponse($errors, 400);
        }

        $this->em->persist($user);
        $this->em->flush();
        return $this->view($user);
    }


    /**
     * @Rest\Get("/api/profile/cards")
     * @Rest\View(serializerGroups={"profileCards"})
     */
    public function getApiProfileCards()
    {
        return $this->view($this->getUser()->getCards());
    }

    /**
     * @Rest\Get("/api/profile/cards/{id}")
     * @Rest\View(serializerGroups={"profileCards"})
     */
    public function getApiProfileCard(Card $card)
    {
        if($card->getUser() !== $this->getUser()) {
            throw new NotFoundHttpException();
        }
        return $this->view($card);
    }

    /**
     * @Rest\Post("/api/profile/cards")
     * @Rest\View(serializerGroups={"profileCards"})
     */
    public function postApiProfileCard(ValidatorInterface $validator, Request $request, CardProvider $cardProvider)
    {
        $card = new Card();

        $card->setUser($this->getUser());

        foreach(static::$postCardRequiredAttributes as $attribute => $setter) {
            if(is_null($request->get($attribute))) {
                continue;
            }
            $card->$setter($request->get($attribute));
        }

        $errors = [];

        $validationErrors = $validator->validate($card);

        /** @var ConstraintViolation $constraintViolation */
        foreach($validationErrors as $constraintViolation) {
            $message = $constraintViolation->getMessage();
            $propertyPath = $constraintViolation->getPropertyPath();
            $errors[] = ['property' => $propertyPath, 'message' => $message];
        }

        // Vérification que le numéro de carte n'est pas déjà utilisé
        $existingCard = $cardProvider->getCardByNumber($card->getCreditCardNumber());
        if(!is_null($existingCard) && $existingCard->getId() !== $card->getId()) {
            $errors[] = ['property' => 'creditCardNumber', 'message' => 'This credit card number is already used'];
        }

        if(!empty($errors)) {
            return new JsonResponse($errors, 400);
        }

        $this->em->persist($card);
        $this->em->flush();
        return $this->view($card, 201);
    }

    /**
     * @Rest\Patch("/api/profile/cards/{id}")
     * @Rest\View(serializerGroups={"profileCards"})
     */
    public function patchApiProfileCard(Card $card, ValidatorInterface $validator, Request $request, CardProvider $cardProvider)
    {
        if($card->getUser() !== $this->getUser()) {
            throw new NotFoundHttpException();
        }

        foreach(static::$patchCardModifiableAttributes as $attribute => $setter) {
            if(is_null($request->get($attribute))) {
                continue;
            }
            $card->$setter($request->get($attribute));
        }

        $errors = [];

        $validationErrors = $validator->validate($card);

        /** @var ConstraintViolation $constraintViolation */
        foreach($validationErrors as $constraintViolation) {
            $message = $constraintViolation->getMessage();
            $propertyPath = $constraintViolation->getPropertyPath();
            $errors[] = ['property' => $propertyPath, 'message' => $message];
        }

        // Vérification que le numéro de carte n'est pas déjà utilisé
        $existingCard = $cardProvider->getCardByNumber($card->getCreditCardNumber());
        if(!is_null($existingCard) && $existingCard->getId() !== $card->getId()) {
            $errors[] = ['property' => 'creditCardNumber', 'message' => 'This credit card number is already used'];
        }

        if(!empty($errors)) {
            return new JsonResponse($errors, 400);
        }

        $this->em->flush();
        return $this->view($card);
    }

    /**
     * @Rest\Delete("/api/profile/cards/{id}")
     */
    public function deleteApiProfileCard(Card $card)
    {
        if($card->getUser() !== $this->getUser()) {
            throw new NotFoundHttpException();
        }

        $this->em->remove($card);
        $this->em->flush();
        return new Response(null, 204);
    }
}