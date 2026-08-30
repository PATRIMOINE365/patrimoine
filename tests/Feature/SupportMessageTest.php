<?php

namespace Tests\Feature;

use App\Mail\SupportMessageMail;
use App\Models\ActivityLog;
use App\Models\Organisation;
use App\Models\User;
use App\Support\OrganisationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * V1.0.36: writing to support from inside Patrimoine.
 *
 * Two things are worth holding here. Every role may write — being unable
 * to do your work is exactly what a Viewer needs to report, and sending
 * them to find an administrator first would only delay it. And the
 * message must carry who wrote it from the session rather than from the
 * form, because an address typed into a box is one anybody could put
 * somebody else's name against.
 */
class SupportMessageTest extends TestCase
{
    use RefreshDatabase;

    private Organisation $organisation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organisation = Organisation::factory()->create([
            'name' => 'Akwaba Property Management',
        ]);

        Mail::fake();
    }

    private function userWithRole(string $role): User
    {
        return OrganisationContext::runAs(
            (int) $this->organisation->id,
            fn (): User => User::factory()
                ->forOrganisation($this->organisation)
                ->create(['role' => $role])
        );
    }

    public function test_an_administrator_can_write_to_support(): void
    {
        $user = $this->userWithRole('administrator');

        Sanctum::actingAs($user);

        $this->postJson('/api/support-messages', [
            'subject' => 'The arrears report will not open',
            'message' => 'It answers PM-5010 every time since this morning.',
        ])->assertOk();

        Mail::assertSent(
            SupportMessageMail::class,
            function (SupportMessageMail $mail) use ($user): bool {
                return $mail->hasTo(config('legal.mailboxes.support'))
                    && $mail->author->is($user)
                    && $mail->subjectLine === 'The arrears report will not open';
            }
        );
    }

    public function test_the_organisation_and_the_writer_travel_with_the_message(): void
    {
        $user = $this->userWithRole('administrator');

        Sanctum::actingAs($user);

        $this->postJson('/api/support-messages', [
            'subject' => 'A question',
            'message' => 'About owner statements.',
        ])->assertOk();

        Mail::assertSent(
            SupportMessageMail::class,
            function (SupportMessageMail $mail): bool {
                $envelope = $mail->envelope();

                return $mail->organisation?->is($this->organisation) === true
                    && str_contains($envelope->subject, 'Akwaba Property Management')
                    && $envelope->replyTo[0]->address === $mail->author->email;
            }
        );
    }

    public function test_a_viewer_may_write_to_support(): void
    {
        Sanctum::actingAs($this->userWithRole('viewer'));

        $this->postJson('/api/support-messages', [
            'subject' => 'I cannot see the owners page',
            'message' => 'It is missing from the menu.',
        ])->assertOk();

        Mail::assertSent(SupportMessageMail::class);
    }

    public function test_a_property_manager_may_write_to_support(): void
    {
        Sanctum::actingAs($this->userWithRole('property_manager'));

        $this->postJson('/api/support-messages', [
            'subject' => 'A payment will not save',
            'message' => 'The drawer closes and nothing is recorded.',
        ])->assertOk();

        Mail::assertSent(SupportMessageMail::class);
    }

    public function test_an_empty_message_is_refused(): void
    {
        Sanctum::actingAs($this->userWithRole('administrator'));

        $this->postJson('/api/support-messages', [
            'subject' => '',
            'message' => '',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['subject', 'message']);

        Mail::assertNothingOutgoing();
    }

    public function test_nobody_signed_out_can_write_to_support(): void
    {
        $this->postJson('/api/support-messages', [
            'subject' => 'Hello',
            'message' => 'Let me in.',
        ])->assertStatus(401);

        Mail::assertNothingOutgoing();
    }

    public function test_sending_is_recorded_in_the_activity_log(): void
    {
        $user = $this->userWithRole('administrator');

        Sanctum::actingAs($user);

        $this->postJson('/api/support-messages', [
            'subject' => 'Invoices are not going out',
            'message' => 'Nothing was sent on the first.',
        ])->assertOk();

        $entry = OrganisationContext::runAs(
            (int) $this->organisation->id,
            fn (): ?ActivityLog => ActivityLog::query()
                ->where('action', 'support.message_sent')
                ->first()
        );

        $this->assertNotNull($entry);
        $this->assertSame('support_message', $entry->entity_type);
        $this->assertSame('Invoices are not going out', $entry->entity_label);
    }

    /**
     * The log records that a message was sent, not what it said.
     * Correspondence with us is not a change to the organisation's
     * records, and an append-only log is the wrong place to keep it.
     */
    public function test_the_activity_log_does_not_keep_the_message_itself(): void
    {
        Sanctum::actingAs($this->userWithRole('administrator'));

        $this->postJson('/api/support-messages', [
            'subject' => 'A subject',
            'message' => 'A sentence nobody should find in the audit trail.',
        ])->assertOk();

        $entry = OrganisationContext::runAs(
            (int) $this->organisation->id,
            fn (): ?ActivityLog => ActivityLog::query()
                ->where('action', 'support.message_sent')
                ->first()
        );

        $this->assertNotNull($entry);

        $this->assertStringNotContainsString(
            'nobody should find',
            json_encode($entry->toArray(), JSON_THROW_ON_ERROR)
        );
    }
}
