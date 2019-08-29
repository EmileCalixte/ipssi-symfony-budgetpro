<?php


namespace App\Controller;


use App\Entity\Subscription;
use App\Repository\SubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Swagger\Annotations as SWG;
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
     * @SWG\Response(
     *     response="200",
     *     description="Returns list of subscriptions"
     * )
     */
    public function getApiSubscriptions()
    {
        $subscriptions = $this->subscriptionRepository->findAll();
        return $this->view($subscriptions);
    }

    /**
     * @Rest\Get("/api/subscriptions/{id}")
     * @Rest\View(serializerGroups={"subscriptions"})
     * @SWG\Parameter(
     *     name="id",
     *     in="path",
     *     type="number",
     *     description="The ID of the subscription",
     *     required=true
     * )
     * @SWG\Response(
     *     response="200",
     *     description="Returns details of the subscription"
     * )
     * @SWG\Response(
     *     response="404",
     *     description="Subscription not found"
     * )
     */
    public function getApiSubscription(Subscription $subscription)
    {
        return $this->view($subscription);
    }

    /** ADMIN */

    /**
     * @Rest\Get("/api/admin/subscriptions")
     * @Rest\View(serializerGroups={"adminSubscriptions"})
     * @SWG\Response(
     *     response="200",
     *     description="Returns list of subscriptions"
     * )
     * @SWG\Response(
     *     response="403",
     *     description="You don't have permission to perform this action"
     * )
     */
    public function getApiAdminSubscriptions()
    {
        $subscriptions = $this->subscriptionRepository->findAll();
        return $this->view($subscriptions);
    }

    /**
     * @Rest\Get("/api/admin/subscriptions/{id}")
     * @Rest\View(serializerGroups={"adminSubscriptions"})
     * @SWG\Parameter(
     *     name="id",
     *     in="path",
     *     type="number",
     *     description="The ID of the subscription",
     *     required=true
     * )
     * @SWG\Response(
     *     response="200",
     *     description="Returns details of the subscription"
     * )
     * @SWG\Response(
     *     response="403",
     *     description="You don't have permission to perform this action"
     * )
     * @SWG\Response(
     *     response="404",
     *     description="Subscription not found"
     * )
     */
    public function getApiAdminSubscription(Subscription $subscription)
    {
        return $this->view($subscription);
    }

    /**
     * @Rest\Post("/api/admin/subscriptions")
     * @ParamConverter("subscription", converter="fos_rest.request_body")
     * @Rest\View(serializerGroups={"adminSubscriptions"})
     * @SWG\Parameter(
     *     name="name",
     *     in="body",
     *     type="string",
     *     description="The name of the subscription",
     *     required=true,
     *     @SWG\Schema(
     *          type="string",
     *          maxLength=255
     *     )
     * )
     * @SWG\Parameter(
     *     name="slogan",
     *     in="body",
     *     type="string",
     *     description="The slogan of the subscription",
     *     required=true,
     *     @SWG\Schema(
     *          type="string",
     *          maxLength=255
     *     )
     * )
     * @SWG\Parameter(
     *     name="url",
     *     in="body",
     *     type="string",
     *     description="The URL of the subscription",
     *     @SWG\Schema(
     *          type="string",
     *          maxLength=255
     *     )
     * )
     * @SWG\Response(
     *     response="201",
     *     description="Subscription created"
     * )
     * @SWG\Response(
     *     response="400",
     *     description="Malformed request body"
     * )
     * @SWG\Response(
     *     response="403",
     *     description="You don't have permission to perform this action"
     * )
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
     * @SWG\Parameter(
     *     name="id",
     *     in="path",
     *     type="number",
     *     description="The ID of the subscription",
     *     required=true
     * )
     * @SWG\Parameter(
     *     name="name",
     *     in="body",
     *     type="string",
     *     description="The name of the subscription",
     *     @SWG\Schema(
     *          type="string",
     *          maxLength=255
     *     )
     * )
     * @SWG\Parameter(
     *     name="slogan",
     *     in="body",
     *     type="string",
     *     description="The slogan of the subscription",
     *     @SWG\Schema(
     *          type="string",
     *          maxLength=255
     *     )
     * )
     * @SWG\Parameter(
     *     name="url",
     *     in="body",
     *     type="string",
     *     description="The URL of the subscription",
     *     @SWG\Schema(
     *          type="string",
     *          maxLength=255
     *     )
     * )
     * @SWG\Response(
     *     response="200",
     *     description="Subscription edited"
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
     *     description="Subscription not found"
     * )
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
     * @SWG\Parameter(
     *     name="id",
     *     in="path",
     *     type="number",
     *     description="The ID of the subscription",
     *     required=true
     * )
     * @SWG\Response(
     *     response="204",
     *     description="Subscription deleted"
     * )
     * @SWG\Response(
     *     response="400",
     *     description="This subscription must have 0 users to be deleted"
     * )
     * @SWG\Response(
     *     response="403",
     *     description="You don't have permission to perform this action"
     * )
     * @SWG\Response(
     *     response="404",
     *     description="Subscription not found"
     * )
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