<?php


namespace App\Controller;


use App\Entity\Subscription;
use App\Repository\SubscriptionRepository;
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

class SubscriptionController extends AbstractFOSRestController
{
    private $subscriptionRepository;
    private $em;

    static private $patchModifiableAttributes = [
        'name' => 'setName',
        'slogan' => 'setSlogan',
        'url' => 'setUrl',
    ];

    public function __construct(SubscriptionRepository $subscriptionRepository, EntityManagerInterface $em)
    {
        $this->subscriptionRepository = $subscriptionRepository;
        $this->em = $em;
    }

    /** ANONYMOUS */

    /**
     * @Rest\Get("/api/subscriptions")
     * @Rest\View(serializerGroups={"subscriptions"})
     */
    public function getApiSubscriptions()
    {
        $subscriptions = $this->subscriptionRepository->findAll();
        return $this->view($subscriptions);
    }

    /**
     * @Rest\Get("/api/subscriptions/{id}")
     * @Rest\View(serializerGroups={"subscriptions"})
     */
    public function getApiSubscription(Subscription $subscription)
    {
        return $this->view($subscription);
    }

    /** ADMIN */

    /**
     * @Rest\Get("/api/admin/subscriptions")
     * @Rest\View(serializerGroups={"adminSubscriptions"})
     */
    public function getApiAdminSubscriptions()
    {
        $subscriptions = $this->subscriptionRepository->findAll();
        return $this->view($subscriptions);
    }

    /**
     * @Rest\Get("/api/admin/subscriptions/{id}")
     * @Rest\View(serializerGroups={"adminSubscriptions"})
     */
    public function getApiAdminSubscription(Subscription $subscription)
    {
        return $this->view($subscription);
    }

    /**
     * @Rest\Post("/api/admin/subscriptions")
     * @ParamConverter("subscription", converter="fos_rest.request_body")
     * @Rest\View(serializerGroups={"adminSubscriptions"})
     */
    public function postApiAdminSubscriptions(Subscription $subscription, ConstraintViolationListInterface $validationErrors)
    {
        $errors = [];

        /** @var ConstraintViolation $constraintViolation */
        foreach($validationErrors as $constraintViolation) {
            $message = $constraintViolation->getMessage();
            $propertyPath = $constraintViolation->getPropertyPath();
            $errors[] = ['property' => $propertyPath, 'message' => $message];
        }

        if(!empty($errors)) {
            return new JsonResponse($errors, 400);
        }

        $this->em->persist($subscription);
        $this->em->flush();
        return $this->view($subscription, 201);
    }

    /**
     * @Rest\Patch("/api/admin/subscriptions/{id}")
     * @Rest\View(serializerGroups={"adminSubscriptions"});
     */
    public function patchApiAdminSubscription(Subscription $subscription, ValidatorInterface $validator, Request $request)
    {
        foreach(static::$patchModifiableAttributes as $attribute => $setter) {
            if(is_null($request->get($attribute))) {
                continue;
            }
            $subscription->$setter($request->get($attribute));
        }

        $errors = [];

        $validationErrors = $validator->validate($subscription);

        /** @var ConstraintViolation $constraintViolation */
        foreach($validationErrors as $constraintViolation) {
            $message = $constraintViolation->getMessage();
            $propertyPath = $constraintViolation->getPropertyPath();
            $errors[] = ['property' => $propertyPath, 'message' => $message];
        }

        if(!empty($errors)) {
            return new JsonResponse($errors, 400);
        }

        $this->em->flush();
        return $this->view($subscription);
    }

    /**
     * @Rest\Delete("/api/admin/subscriptions/{id}")
     */
    public function deleteApiAdminSubscription(Subscription $subscription)
    {
        if($subscription->getUsers()->count() > 0) {
            return new JsonResponse([
                'message' => 'This subscription has users'
            ], 400);
        }

        $this->em->remove($subscription);
        $this->em->flush();
        return new Response(null, 204);
    }
}