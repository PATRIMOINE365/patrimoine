<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\BuildingOwner;
use App\Models\OwnerAccount;
use App\Models\OwnerTransaction;
use App\Models\Party;
use App\Models\User;
use App\Services\DocumentLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AuthenticatesApiUser;
use Tests\TestCase;

/**
 * Verifies signed document links.
 *
 * A signed link lets a browser tab navigate straight to a PDF endpoint
 * without carrying the Sanctum Bearer token. The link must:
 *
 * - only ever be issued for whitelisted read-only document endpoints;
 * - authenticate exactly the signed user, and only while that user is
 *   active;
 * - still pass through the route's own capability authorization;
 * - die on any tampering and after its expiry time.
 */
class DocumentLinkApiTest extends TestCase
{
    use AuthenticatesApiUser;
    use RefreshDatabase;

    /**
     * Build an Owner Deposit whose receipt PDF can be requested.
     */
    private function createOwnerDeposit(): OwnerTransaction
    {
        $building = Building::create([
            'name' => 'Signed Link Building',
        ]);

        $owner = Party::create([
            'type' => 'person',
            'name' => 'Signed Link Owner',
            'phone' => '0200001600',
            'email' => 'signed-link-owner@example.test',
        ]);

        BuildingOwner::create([
            'building_id' => $building->id,
            'party_id' => $owner->id,
            'ownership_percentage' => 100.00,
        ]);

        $account = OwnerAccount::query()
            ->where('party_id', $owner->id)
            ->firstOrFail();

        return OwnerTransaction::create([
            'owner_account_id' => $account->id,
            'building_id' => $building->id,
            'direction' => 'credit',
            'category' => 'owner_deposit',
            'amount' => 10000,
            'transaction_date' => '2026-08-25',
            'payment_method' => 'bank_transfer',
            'deposit_purpose' => 'general_funding',
            'notes' => 'Signed link test deposit.',
        ]);
    }

    public function test_authenticated_user_receives_a_signed_link(): void
    {
        $this->authenticateApiUser();

        $deposit = $this->createOwnerDeposit();

        $response = $this->postJson(
            '/api/document-links',
            [
                'endpoint' => "/api/owner-deposits/{$deposit->id}/receipt",
            ]
        );

        $response->assertOk();

        $url = $response->json('url');

        $this->assertIsString($url);
        $this->assertStringContainsString('user=', $url);
        $this->assertStringContainsString('expires=', $url);
        $this->assertStringContainsString('signature=', $url);
    }

    public function test_signed_link_serves_the_document_without_a_bearer_token(): void
    {
        $deposit = $this->createOwnerDeposit();

        $user = User::factory()->create();

        $url = app(DocumentLinkService::class)->issue(
            "/api/owner-deposits/{$deposit->id}/receipt",
            $user
        );

        $response = $this->get($url);

        $response->assertOk();
        $response->assertHeader(
            'Content-Type',
            'application/pdf'
        );
    }

    public function test_signed_link_preserves_the_document_query_string(): void
    {
        $user = User::factory()->create([
            'role' => 'administrator',
        ]);

        $url = app(DocumentLinkService::class)->issue(
            '/api/registry/export/pdf?entity=parties',
            $user
        );

        $response = $this->get($url);

        $response->assertOk();
        $response->assertHeader(
            'Content-Type',
            'application/pdf'
        );
    }

    public function test_tampered_signed_link_is_rejected(): void
    {
        $deposit = $this->createOwnerDeposit();

        $user = User::factory()->create();

        $other = User::factory()->create([
            'role' => 'administrator',
        ]);

        $url = app(DocumentLinkService::class)->issue(
            "/api/owner-deposits/{$deposit->id}/receipt",
            $user
        );

        $tampered = str_replace(
            'user='.$user->id,
            'user='.$other->id,
            $url
        );

        $this->get($tampered)
            ->assertForbidden();
    }

    public function test_expired_signed_link_is_rejected(): void
    {
        $deposit = $this->createOwnerDeposit();

        $user = User::factory()->create();

        $url = app(DocumentLinkService::class)->issue(
            "/api/owner-deposits/{$deposit->id}/receipt",
            $user
        );

        $this->travel(11)->minutes();

        $this->get($url)
            ->assertForbidden();
    }

    public function test_signed_link_for_a_disabled_user_is_rejected(): void
    {
        $deposit = $this->createOwnerDeposit();

        $user = User::factory()->create();

        $url = app(DocumentLinkService::class)->issue(
            "/api/owner-deposits/{$deposit->id}/receipt",
            $user
        );

        $user->update([
            'is_active' => false,
        ]);

        $this->get($url)
            ->assertForbidden();
    }

    public function test_signed_user_still_passes_capability_authorization(): void
    {
        $viewer = User::factory()->create([
            'role' => 'viewer',
        ]);

        /*
         * Registry exports require manage_settings, which Viewer does
         * not hold. The signature must authenticate without
         * authorizing.
         */
        $url = app(DocumentLinkService::class)->issue(
            '/api/registry/export/pdf?entity=parties',
            $viewer
        );

        $this->get($url)
            ->assertForbidden();
    }

    public function test_non_document_endpoints_are_never_signed(): void
    {
        $this->authenticateApiUser();

        $this->postJson(
            '/api/document-links',
            [
                'endpoint' => '/api/parties',
            ]
        )->assertUnprocessable();

        $this->postJson(
            '/api/document-links',
            [
                'endpoint' => '/api/users',
            ]
        )->assertUnprocessable();
    }

    public function test_reserved_parameters_cannot_be_smuggled_into_the_endpoint(): void
    {
        $this->authenticateApiUser();

        $this->postJson(
            '/api/document-links',
            [
                'endpoint' => '/api/registry/export/pdf?entity=parties&user=999',
            ]
        )->assertUnprocessable();
    }

    public function test_document_requests_without_a_signature_still_require_authentication(): void
    {
        $deposit = $this->createOwnerDeposit();

        $this->getJson(
            "/api/owner-deposits/{$deposit->id}/receipt"
        )->assertUnauthorized();
    }
}
