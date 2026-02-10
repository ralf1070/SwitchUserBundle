<?php

namespace KimaiPlugin\SwitchUserBundle\Controller;

use App\Controller\AbstractController;
use App\Repository\Query\UserQuery;
use App\Repository\UserRepository;
use App\Utils\PageSetup;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/admin')]
#[IsGranted('ROLE_SUPER_ADMIN')]
final class SwitchUserController extends AbstractController
{
    #[Route(path: '/switch-user', name: 'switch_user_list', methods: ['GET'])]
    public function list(UserRepository $userRepository): Response
    {
        $query = new UserQuery();
        $query->setSystemAccount(false);
        $users = $userRepository->getUsersForQuery($query);

        $page = new PageSetup('switch_user');

        return $this->render('@SwitchUser/switch.html.twig', [
            'page_setup' => $page,
            'users' => $users,
            'currentUser' => $this->getUser(),
        ]);
    }
}
