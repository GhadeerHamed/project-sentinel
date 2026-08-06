<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Infrastructure\Authentication\AuthenticatedIdentityResolver;
use App\Infrastructure\Authentication\IdentityUserAuthenticatable;
use App\Infrastructure\Persistence\IdentityAccess\Records\IdentityCredentialRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Sentinel\Shared\Domain\Identifier\UuidV7Generator;
use Tests\TestCase;

final class HumanUserSessionAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private int $requestNumber = 0;

    #[Test]
    public function an_active_human_user_can_start_a_password_authenticated_session(): void
    {
        $user = $this->identityUser(status: 'active');

        $this->csrfProtectedPost('/login', [
            'email' => $user->email,
            'password' => 'correct-password',
        ])->assertNoContent();

        $this->assertAuthenticatedAs($user, 'web');

        $identity = $this->app->make(AuthenticatedIdentityResolver::class)->currentIdentity();

        self::assertNotNull($identity);
        self::assertSame($user->identity_id, $identity->identityId());
        self::assertSame('user', $identity->identityType());
    }

    #[Test]
    public function it_rejects_invalid_passwords_without_starting_a_session(): void
    {
        $user = $this->identityUser(status: 'active');

        $this->csrfProtectedPost('/login', [
            'email' => $user->email,
            'password' => 'incorrect-password',
        ])->assertRedirect('/');

        $this->assertGuest('web');
    }

    #[Test]
    public function it_rejects_non_authenticatable_identity_statuses(): void
    {
        foreach (['suspended', 'disabled', 'compromised'] as $status) {
            $user = $this->identityUser(status: $status, email: sprintf('%s@example.test', $status));

            $this->csrfProtectedPost('/login', [
                'email' => $user->email,
                'password' => 'correct-password',
            ])->assertRedirect('/');

            $this->assertGuest('web');
        }
    }

    #[Test]
    public function an_authenticated_human_user_can_log_out(): void
    {
        $user = $this->identityUser(status: 'active');

        $this->actingAs($user, 'web');

        $this->csrfProtectedPost('/logout')->assertNoContent();

        $this->assertGuest('web');
    }

    private function identityUser(string $status, string $email = 'user@example.test'): IdentityUserAuthenticatable
    {
        $user = new IdentityUserAuthenticatable;
        $user->forceFill([
            'identity_id' => $this->app->make(UuidV7Generator::class)->generate(),
            'email' => $email,
            'display_name' => 'Authenticated User',
            'status' => $status,
            'version' => 1,
        ]);
        $user->save();

        $credential = new IdentityCredentialRecord;
        $credential->forceFill([
            'id' => $this->app->make(UuidV7Generator::class)->generate(),
            'identity_id' => $user->identity_id,
            'credential_type' => 'password',
            'secret_reference' => Hash::make('correct-password'),
            'status' => 'active',
            'created_at' => now(),
        ]);
        $credential->save();

        return $user;
    }

    /**
     * @param  array<string, string>  $data
     */
    private function csrfProtectedPost(string $uri, array $data = []): TestResponse
    {
        return $this->withSession(['_token' => 'csrf-token'])
            ->withHeader('X-CSRF-TOKEN', 'csrf-token')
            ->withServerVariables([
                'REMOTE_ADDR' => sprintf('192.0.2.%d', ++$this->requestNumber),
            ])
            ->post($uri, $data);
    }
}
