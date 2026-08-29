<?php

namespace Tests\Feature;

use App\Models\LeaseWizardDraft;
use App\Models\Organisation;
use App\Models\User;
use App\Support\OrganisationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AuthenticatesApiUser;
use Tests\TestCase;

/**
 * V1.0.31: somewhere to put an unfinished assistant.
 *
 * A lease cannot be saved half-made — unit_id and tenant_id are required
 * columns — so "save as draft" from page three had nowhere to go. What is
 * saved is the assistant itself, and the point of these tests is that it
 * is saved WITHOUT being validated: half of it is expected to be blank.
 */
class LeaseWizardDraftTest extends TestCase
{
    use AuthenticatesApiUser;
    use RefreshDatabase;

    private User $me;

    protected function setUp(): void
    {
        parent::setUp();

        $this->me = $this->authenticateApiUser('administrator');
    }

    public function test_an_almost_empty_assistant_is_saved(): void
    {
        $this
            ->postJson('/api/lease-wizard/drafts', [
                'payload' => [
                    'step' => 2,
                    'fields' => [
                        'wizard-building-mode' => 'new',
                    ],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('draft.author', $this->me->name);

        $this->assertSame(
            1,
            LeaseWizardDraft::query()->count()
        );

        /*
         * Nothing about a lease was created: there is no lease to make
         * until a unit and a tenant exist.
         */
        $this->assertSame(
            0,
            \App\Models\Lease::query()->count()
        );
    }

    public function test_a_completely_empty_assistant_is_saved_too(): void
    {
        $this
            ->postJson('/api/lease-wizard/drafts', [
                'payload' => [
                    'step' => 1,
                    'fields' => [],
                ],
            ])
            ->assertCreated();

        $this->assertSame(
            1,
            LeaseWizardDraft::query()->count()
        );
    }

    public function test_saving_the_same_assistant_twice_leaves_one(): void
    {
        $first = $this
            ->postJson('/api/lease-wizard/drafts', [
                'payload' => ['step' => 2, 'fields' => []],
            ])
            ->assertCreated()
            ->json('draft.id');

        $this
            ->postJson('/api/lease-wizard/drafts', [
                'id' => $first,
                'payload' => ['step' => 5, 'fields' => ['wizard-rent-amount' => '1200']],
            ])
            ->assertOk()
            ->assertJsonPath('draft.id', $first);

        $this->assertSame(
            1,
            LeaseWizardDraft::query()->count()
        );

        $this->assertSame(
            5,
            LeaseWizardDraft::query()->firstOrFail()->payload['step']
        );
    }

    public function test_it_comes_back_exactly_as_it_was_left(): void
    {
        $payload = [
            'step' => 7,
            'owner_rows' => 2,
            'fields' => [
                'wizard-building-mode' => 'new',
                'wizard-building-name' => 'Cocody Court',
                'wizard-rent-amount' => '250000',
            ],
        ];

        $id = $this
            ->postJson('/api/lease-wizard/drafts', ['payload' => $payload])
            ->assertCreated()
            ->json('draft.id');

        $this
            ->getJson("/api/lease-wizard/drafts/{$id}")
            ->assertOk()
            ->assertJsonPath('payload.step', 7)
            ->assertJsonPath('payload.owner_rows', 2)
            ->assertJsonPath('payload.fields.wizard-building-name', 'Cocody Court');
    }

    public function test_it_is_named_from_who_started_it_and_when(): void
    {
        $response = $this
            ->postJson('/api/lease-wizard/drafts', [
                'payload' => ['step' => 1, 'fields' => []],
            ])
            ->assertCreated();

        $this->assertSame(
            $this->me->name,
            $response->json('draft.author')
        );

        /*
         * The moment travels rather than a sentence, so the list can read
         * it in the language of whoever opens the page.
         */
        $this->assertNotNull(
            $response->json('draft.started_at')
        );

        $this->assertNotFalse(
            strtotime((string) $response->json('draft.started_at'))
        );
    }

    public function test_the_name_survives_the_account_that_started_it(): void
    {
        $id = $this
            ->postJson('/api/lease-wizard/drafts', [
                'payload' => ['step' => 1, 'fields' => []],
            ])
            ->json('draft.id');

        $draft = LeaseWizardDraft::query()->findOrFail($id);

        $this->assertSame($this->me->name, $draft->author_name);

        $draft->user()->dissociate()->save();

        $this->assertSame(
            $this->me->name,
            $draft->refresh()->author_name,
            'The list should still read sensibly once the account is gone.'
        );
    }

    public function test_one_can_be_thrown_away(): void
    {
        $id = $this
            ->postJson('/api/lease-wizard/drafts', [
                'payload' => ['step' => 3, 'fields' => []],
            ])
            ->json('draft.id');

        $this
            ->deleteJson("/api/lease-wizard/drafts/{$id}")
            ->assertOk();

        $this->assertSame(
            0,
            LeaseWizardDraft::query()->count()
        );

        $this
            ->getJson("/api/lease-wizard/drafts/{$id}")
            ->assertNotFound();
    }

    public function test_the_list_is_newest_touched_first(): void
    {
        $older = LeaseWizardDraft::create([
            'user_id' => $this->me->id,
            'author_name' => 'Older',
            'payload' => ['step' => 1, 'fields' => []],
        ]);

        $older->forceFill(['updated_at' => now()->subDay()])->saveQuietly();

        LeaseWizardDraft::create([
            'user_id' => $this->me->id,
            'author_name' => 'Newer',
            'payload' => ['step' => 1, 'fields' => []],
        ]);

        $authors = array_column(
            $this->getJson('/api/lease-wizard/drafts')->assertOk()->json('data'),
            'author'
        );

        $this->assertSame(['Newer', 'Older'], $authors);
    }

    public function test_another_organisation_never_sees_one(): void
    {
        $mine = $this
            ->postJson('/api/lease-wizard/drafts', [
                'payload' => ['step' => 4, 'fields' => []],
            ])
            ->json('draft.id');

        $other = Organisation::factory()->create();

        OrganisationContext::runAs(
            (int) $other->id,
            function () use ($other): void {
                $stranger = User::factory()->create([
                    'organisation_id' => $other->id,
                    'role' => 'administrator',
                ]);

                \Laravel\Sanctum\Sanctum::actingAs($stranger);
            }
        );

        $this
            ->getJson('/api/lease-wizard/drafts')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this
            ->getJson("/api/lease-wizard/drafts/{$mine}")
            ->assertNotFound();

        $this
            ->deleteJson("/api/lease-wizard/drafts/{$mine}")
            ->assertNotFound();

        $this->assertSame(
            1,
            LeaseWizardDraft::withoutGlobalScopes()->count(),
            'The other organisation\'s assistant must still be there.'
        );
    }

    public function test_a_viewer_cannot_save_one(): void
    {
        $this->authenticateApiUser('viewer');

        $this
            ->postJson('/api/lease-wizard/drafts', [
                'payload' => ['step' => 1, 'fields' => []],
            ])
            ->assertForbidden();
    }

    public function test_a_payload_is_required(): void
    {
        $this
            ->postJson('/api/lease-wizard/drafts', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['payload']);
    }
}
