<?php

namespace KimaiPlugin\SwitchUserBundle\EventSubscriber;

use App\Utils\MenuItemModel;
use KevinPapst\TablerBundle\Event\UserDetailsEvent;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class UserMenuSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private AuthorizationCheckerInterface $auth,
        private Security $security,
        private TranslatorInterface $translator,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            UserDetailsEvent::class => ['onUserDetailsEvent', 50],
        ];
    }

    public function onUserDetailsEvent(UserDetailsEvent $event): void
    {
        $token = $this->security->getToken();

        if ($token instanceof SwitchUserToken) {
            $originalUser = $token->getOriginalToken()->getUser();
            $originalName = $originalUser !== null ? $originalUser->getUserIdentifier() : '?';

            // Pre-translate because the dropdown template uses |trans without parameters
            $label = $this->translator->trans('switch_user.back_to', ['%name%' => $originalName]);

            $event->addLink(new MenuItemModel(
                'switch_user_exit',
                $label,
                'homepage',
                ['_switch_user' => '_exit'],
                'fas fa-user-shield'
            ));

            return;
        }

        if (!$this->auth->isGranted('ROLE_SUPER_ADMIN')) {
            return;
        }

        // 'switch_user' is a translation key — resolved by |trans in the dropdown template
        $event->addLink(new MenuItemModel(
            'switch_user',
            'switch_user',
            'switch_user_list',
            [],
            'fas fa-people-arrows'
        ));
    }
}
