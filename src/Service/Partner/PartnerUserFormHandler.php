<?php

namespace App\Service\Partner;

use App\Entity\Partner;
use App\Entity\User;
use App\Factory\UserFactory;
use App\Manager\UserManager;
use App\Service\NotificationService;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Security\Http\LoginLink\LoginLinkHandlerInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PartnerUserFormHandler
{
    public function __construct(private UserManager $userManager,
                                private UserFactory $userFactory,
                                private ValidatorInterface $validator,
                                private LoginLinkHandlerInterface $loginLinkHandler,
                                private NotificationService $notificationService
    ) {
    }

    public function handle(FormInterface $form, Partner $partner)
    {
        if (isset($form->getExtraData()['users'])) {
            foreach ($form->getExtraData()['users'] as $userId => $userData) {
                if ('new' !== $userId) {
                    $user = $this->userManager->getUserFrom($partner, $userId);
                    if (null !== $user) {
                        $user = $this->userManager->updateUserFromData($user, $userData);
                        $this->throwException($user);
                        $this->userManager->persist($user);
                    }
                } else {
                    foreach ($userData as $userDataItem) {
                        $user = $this->userFactory->createInstanceFromArray($partner, $userDataItem);
                        $this->throwException($user);
                        $this->userManager->persist($user);
                        $this->sendNotification($user);
                    }
                }
            }
        }
    }

    private function throwException(User $user): void
    {
        /** @var ConstraintViolationList $errors */
        $errors = $this->validator->validate($user);
        if (\count($errors) > 0) {
            foreach ($errors as $error) {
                throw new \Exception($error->getMessage());
            }
        }
    }

    private function sendNotification(User $user): void
    {
        $loginLinkDetails = $this->loginLinkHandler->createLoginLink($user);
        $loginLink = $loginLinkDetails->getUrl();
        $this->notificationService->send(
            NotificationService::TYPE_ACCOUNT_ACTIVATION,
            $user->getEmail(),
            ['link' => $loginLink],
            $user->getTerritory()
        );
    }
}
