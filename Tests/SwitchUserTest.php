<?php

namespace KimaiPlugin\SwitchUserBundle\Tests;

use App\DataFixtures\UserFixtures;
use App\Entity\User;
use App\Tests\Controller\AbstractControllerBaseTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use KimaiPlugin\SwitchUserBundle\Controller\SwitchUserController;
use KimaiPlugin\SwitchUserBundle\EventSubscriber\SwitchUserRequestListener;
use KimaiPlugin\SwitchUserBundle\EventSubscriber\UserMenuSubscriber;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[CoversClass(SwitchUserController::class)]
#[CoversClass(SwitchUserRequestListener::class)]
#[CoversClass(UserMenuSubscriber::class)]
#[Group('integration')]
class SwitchUserTest extends AbstractControllerBaseTestCase
{
    // ========== Zugriffsschutz: Switch-User-Seite ==========

    public function testSwitchUserPageRequiresLogin(): void
    {
        $this->assertUrlIsSecured('admin/switch-user');
    }

    public function testSwitchUserPageDeniedForUser(): void
    {
        $this->assertUrlIsSecuredForRole(User::ROLE_USER, 'admin/switch-user');
    }

    public function testSwitchUserPageDeniedForTeamlead(): void
    {
        $this->assertUrlIsSecuredForRole(User::ROLE_TEAMLEAD, 'admin/switch-user');
    }

    public function testSwitchUserPageDeniedForAdmin(): void
    {
        $this->assertUrlIsSecuredForRole(User::ROLE_ADMIN, 'admin/switch-user');
    }

    public function testSwitchUserPageAccessibleForSuperAdmin(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->request($client, 'admin/switch-user');

        self::assertTrue($client->getResponse()->isSuccessful());
        self::assertStringContainsString('switch-user-table', $client->getResponse()->getContent());
    }

    // ========== Switch-User-Seite: Inhalt ==========

    public function testSwitchUserPageShowsUsers(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->request($client, 'admin/switch-user');

        $content = $client->getResponse()->getContent();

        // Fixture users should be listed (except the super-admin herself)
        self::assertStringContainsString(UserFixtures::USERNAME_USER, $content);
        self::assertStringContainsString(UserFixtures::USERNAME_ADMIN, $content);
        self::assertStringContainsString(UserFixtures::USERNAME_TEAMLEAD, $content);
        self::assertStringNotContainsString(
            '_switch_user=' . UserFixtures::USERNAME_SUPER_ADMIN,
            $content,
            'Super-Admin should not be listed as switch target'
        );
    }

    // ========== Web: Impersonation funktioniert ==========

    public function testSuperAdminCanSwitchUser(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);

        $crawler = $client->request('GET', $this->createUrl('admin/switch-user'));
        self::assertTrue($client->getResponse()->isSuccessful());

        // Click on the switch link for john_user
        $link = $crawler->filter('a[href*="_switch_user=' . UserFixtures::USERNAME_USER . '"]');
        self::assertGreaterThan(0, $link->count(), 'Switch link for john_user not found');

        // Follow the full redirect chain (switch → homepage → timesheet etc.)
        $client->followRedirects(true);
        $client->click($link->link());

        self::assertTrue($client->getResponse()->isSuccessful());
    }

    public function testSuperAdminCanExitImpersonation(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);

        // Switch to user via the switch page link
        $client->followRedirects(true);
        $crawler = $client->request('GET', $this->createUrl('admin/switch-user'));
        $link = $crawler->filter('a[href*="_switch_user=' . UserFixtures::USERNAME_USER . '"]');
        $client->click($link->link());

        // Exit impersonation (follows full redirect chain)
        $client->request('GET', $this->createUrl('homepage'), ['_switch_user' => '_exit']);
        self::assertTrue($client->getResponse()->isSuccessful());
    }

    // ========== Web: Nicht-Super-Admins können nicht wechseln ==========

    public function testAdminCannotSwitchUser(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);

        $this->expectException(AccessDeniedException::class);
        $client->request('GET', $this->createUrl('timesheet'), ['_switch_user' => UserFixtures::USERNAME_USER]);
    }

    public function testTeamleadCannotSwitchUser(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);

        $this->expectException(AccessDeniedException::class);
        $client->request('GET', $this->createUrl('timesheet'), ['_switch_user' => UserFixtures::USERNAME_USER]);
    }

    public function testUserCannotSwitchUser(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $this->expectException(AccessDeniedException::class);
        $client->request('GET', $this->createUrl('timesheet'), ['_switch_user' => UserFixtures::USERNAME_ADMIN]);
    }

    // ========== API: Switch-User wird blockiert ==========

    public function testApiIgnoresSwitchUserParameter(): void
    {
        $client = self::createClient([], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . UserFixtures::DEFAULT_API_TOKEN . '_super',
        ]);

        $client->request('GET', '/api/timesheets', ['_switch_user' => UserFixtures::USERNAME_USER]);

        // API should work normally — the _switch_user parameter is ignored
        self::assertTrue(
            $client->getResponse()->isSuccessful(),
            'API request with _switch_user should succeed normally (param ignored), got ' . $client->getResponse()->getStatusCode()
        );

        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertIsArray($data);
    }

    public function testApiIgnoresSwitchUserExit(): void
    {
        $client = self::createClient([], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . UserFixtures::DEFAULT_API_TOKEN . '_super',
        ]);

        $client->request('GET', '/api/timesheets', ['_switch_user' => '_exit']);

        self::assertTrue(
            $client->getResponse()->isSuccessful(),
            'API request with _switch_user=_exit should succeed normally, got ' . $client->getResponse()->getStatusCode()
        );
    }
}
