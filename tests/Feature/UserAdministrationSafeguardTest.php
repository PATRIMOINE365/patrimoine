<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\UserAdministrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Verify V1.0.3 Administrator lockout safeguards independently from the
 * User Management HTTP endpoints that will be added in the next activity.
 */
class UserAdministrationSafeguardTest extends TestCase
{
    use RefreshDatabase;

    private UserAdministrationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(
            UserAdministrationService::class
        );
    }

    public function test_administrator_cannot_change_own_role(): void
    {
        $administrator = $this->administrator();

        $this->expectValidationMessage(
            'role',
            'An administrator’s own role has to be changed by another administrator.',
            fn () => $this->service->changeRole(
                $administrator,
                $administrator,
                UserRole::PropertyManager
            )
        );

        $this->assertDatabaseHas('users', [
            'id' => $administrator->id,
            'role' => UserRole::Administrator->value,
        ]);
    }

    public function test_administrator_cannot_disable_self(): void
    {
        $administrator = $this->administrator();

        $this->expectValidationMessage(
            'is_active',
            'An account cannot switch itself off. Another administrator can do it.',
            fn () => $this->service->changeActiveStatus(
                $administrator,
                $administrator,
                false
            )
        );

        $this->assertDatabaseHas('users', [
            'id' => $administrator->id,
            'is_active' => true,
        ]);
    }

    public function test_administrator_cannot_delete_self(): void
    {
        $administrator = $this->administrator();

        $this->expectValidationMessage(
            'user',
            'An account cannot delete itself. Another administrator can do it.',
            fn () => $this->service->delete(
                $administrator,
                $administrator
            )
        );

        $this->assertDatabaseHas('users', [
            'id' => $administrator->id,
        ]);
    }

    public function test_last_active_administrator_cannot_be_demoted(): void
    {
        $actor = $this->administrator([
            'is_active' => false,
        ]);

        $target = $this->administrator();

        $this->expectValidationMessage(
            'administrator',
            'This action cannot be completed because Patrimoine must retain at least one active Administrator.',
            fn () => $this->service->changeRole(
                $actor,
                $target,
                UserRole::Viewer
            )
        );

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'role' => UserRole::Administrator->value,
            'is_active' => true,
        ]);
    }

    public function test_last_active_administrator_cannot_be_disabled(): void
    {
        $actor = $this->administrator([
            'is_active' => false,
        ]);

        $target = $this->administrator();

        $this->expectValidationMessage(
            'administrator',
            'This action cannot be completed because Patrimoine must retain at least one active Administrator.',
            fn () => $this->service->changeActiveStatus(
                $actor,
                $target,
                false
            )
        );

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'is_active' => true,
        ]);
    }

    public function test_last_active_administrator_cannot_be_deleted(): void
    {
        $actor = $this->administrator([
            'is_active' => false,
        ]);

        $target = $this->administrator();

        $this->expectValidationMessage(
            'administrator',
            'This action cannot be completed because Patrimoine must retain at least one active Administrator.',
            fn () => $this->service->delete(
                $actor,
                $target
            )
        );

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
        ]);
    }

    public function test_administrator_can_demote_another_when_one_remains(): void
    {
        $actor = $this->administrator();
        $target = $this->administrator();

        $updated = $this->service->changeRole(
            $actor,
            $target,
            UserRole::PropertyManager
        );

        $this->assertSame(
            UserRole::PropertyManager,
            $updated->role
        );

        $this->assertTrue(
            $actor->fresh()->isActive()
        );
    }

    public function test_administrator_can_disable_another_when_one_remains(): void
    {
        $actor = $this->administrator();
        $target = $this->administrator();

        $updated = $this->service->changeActiveStatus(
            $actor,
            $target,
            false
        );

        $this->assertFalse(
            $updated->isActive()
        );

        $this->assertTrue(
            $actor->fresh()->isActive()
        );
    }

    public function test_administrator_can_delete_another_when_one_remains(): void
    {
        $actor = $this->administrator();
        $target = $this->administrator();

        $this->service->delete(
            $actor,
            $target
        );

        $this->assertDatabaseMissing('users', [
            'id' => $target->id,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $actor->id,
            'role' => UserRole::Administrator->value,
            'is_active' => true,
        ]);
    }

    public function test_inactive_administrator_does_not_satisfy_safeguard(): void
    {
        $actor = $this->administrator([
            'is_active' => false,
        ]);

        $target = $this->administrator();

        $this->administrator([
            'is_active' => false,
        ]);

        $this->expectValidationMessage(
            'administrator',
            'This action cannot be completed because Patrimoine must retain at least one active Administrator.',
            fn () => $this->service->changeRole(
                $actor,
                $target,
                UserRole::PropertyManager
            )
        );
    }

    public function test_no_op_own_role_update_is_allowed(): void
    {
        $administrator = $this->administrator();

        $updated = $this->service->changeRole(
            $administrator,
            $administrator,
            UserRole::Administrator
        );

        $this->assertSame(
            UserRole::Administrator,
            $updated->role
        );
    }

    public function test_no_op_own_active_status_update_is_allowed(): void
    {
        $administrator = $this->administrator();

        $updated = $this->service->changeActiveStatus(
            $administrator,
            $administrator,
            true
        );

        $this->assertTrue(
            $updated->isActive()
        );
    }

    public function test_non_administrator_user_mutations_are_not_blocked(): void
    {
        $actor = $this->administrator();

        $target = User::factory()->create([
            'role' => UserRole::PropertyManager,
            'is_active' => true,
        ]);

        $updated = $this->service->changeRole(
            $actor,
            $target,
            UserRole::Viewer
        );

        $this->assertSame(
            UserRole::Viewer,
            $updated->role
        );

        $updated = $this->service->changeActiveStatus(
            $actor,
            $updated,
            false
        );

        $this->assertFalse(
            $updated->isActive()
        );

        $this->service->delete(
            $actor,
            $updated
        );

        $this->assertDatabaseMissing('users', [
            'id' => $target->id,
        ]);
    }

    public function test_safeguard_message_renders_in_french(): void
    {
        App::setLocale('fr');

        $administrator = $this->administrator();

        $this->expectValidationMessage(
            'is_active',
            'Un compte ne peut pas se désactiver lui-même. Un autre administrateur peut le faire.',
            fn () => $this->service->changeActiveStatus(
                $administrator,
                $administrator,
                false
            )
        );
    }

    /**
     * Create an Administrator with explicit defaults for safeguard tests.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function administrator(
        array $attributes = []
    ): User {
        return User::factory()->create(
            array_merge(
                [
                    'role' => UserRole::Administrator,
                    'is_active' => true,
                ],
                $attributes
            )
        );
    }

    /**
     * Assert a localized ValidationException without depending on an HTTP
     * controller that does not exist until the next User Management activity.
     */
    private function expectValidationMessage(
        string $field,
        string $expectedMessage,
        callable $operation
    ): void {
        try {
            $operation();

            $this->fail(
                'Expected a ValidationException to be thrown.'
            );
        } catch (ValidationException $exception) {
            $messages = $exception->errors();

            $this->assertArrayHasKey(
                $field,
                $messages
            );

            $this->assertSame(
                $expectedMessage,
                $messages[$field][0]
            );
        }
    }
}
