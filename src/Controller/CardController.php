<?php


namespace App\Controller;


use App\Entity\Card;
use App\Provider\CardProvider;
use App\Repository\CardRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CardController extends AbstractFOSRestController
{
    private $cardRepository;
    private $em;

    private static $postRequiredAttributes = [
        // 'userId' => Vérifié autrement car il n'y a pas directement de setter par ID
        'name' => 'setName',
        'creditCardType' => 'setCreditCardType',
        'creditCardNumber' => 'setCreditCardNumber',
        'currencyCode' => 'setCurrencyCode',
        'value' => 'setValue',
    ];

    private static $patchModifiableAttributes = [
        'name' => 'setName',
        'creditCardType' => 'setCreditCardType',
        'creditCardNumber' => 'setCreditCardNumber',
        'currencyCode' => 'setCurrencyCode',
        'value' => 'setValue',
    ];

    public function __construct(CardRepository $cardRepository, EntityManagerInterface $em)
    {
        $this->cardRepository = $cardRepository;
        $this->em = $em;
    }

    /**
     * @Rest\Get("/api/admin/cards")
     * @Rest\View(serializerGroups={"adminCards"})
     */
    public function getApiAdminCards()
    {
        $cards = $this->cardRepository->findAll();
        return $this->view($cards);
    }

    /**
     * @Rest\Get("/api/admin/cards/{id}")
     * @Rest\View(serializerGroups={"adminCards"})
     */
    public function getApiAdminCard(Card $card)
    {
        return $this->view($card);
    }

    /**
     * @Rest\Post("/api/admin/cards")
     * @Rest\View(serializerGroups={"adminCards"})
     */
    public function postApiAdminCard(ValidatorInterface $validator, Request $request, UserRepository $userRepository, CardProvider $cardProvider)
    {
        $card = new Card();

        foreach(static::$postRequiredAttributes as $attribute => $setter) {
            if(is_null($request->get($attribute))) {
                continue;
            }
            $card->$setter($request->get($attribute));
        }

        // Vérification manuelle de l'ID du user
        if(!is_null($request->get('userId'))) {
            $user = $userRepository->find($request->get('userId'));
            if(!is_null($user)) {
                $card->setUser($user);
            }
        }

        $errors = [];

        $validationErrors = $validator->validate($card);

        /** @var ConstraintViolation $constraintViolation */
        foreach($validationErrors as $constraintViolation) {
            $message = $constraintViolation->getMessage();
            $propertyPath = $constraintViolation->getPropertyPath();
            if($propertyPath === 'user') $propertyPath = 'userId';
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
     * @Rest\Patch("/api/admin/cards/{id}")
     * @Rest\View(serializerGroups={"adminCards"})
     */
    public function patchApiAdminCard(Card $card, ValidatorInterface $validator, Request $request, CardProvider $cardProvider)
    {
        foreach(static::$patchModifiableAttributes as $attribute => $setter) {
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
     * @Rest\Delete("/api/admin/cards/{id}")
     */
    public function deleteApiAdminCard(Card $card)
    {
        $this->em->remove($card);
        $this->em->flush();
        return new Response(null, 204);
    }
}