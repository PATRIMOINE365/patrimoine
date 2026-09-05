<?php

namespace Tests\Feature;

use App\Mail\EmailVerificationMail;
use App\Mail\InvoiceMail;
use App\Mail\MfaCodeMail;
use App\Mail\OwnerExpenseBillMail;
use App\Mail\OwnerReserveTransferVoucherMail;
use App\Mail\ReceiptMail;
use App\Mail\RentIncrementNoticeMail;
use App\Mail\RentReminderMail;
use App\Mail\SupportMessageMail;
use App\Mail\TenantFundExpenseVoucherMail;
use App\Mail\TenantFundTransferVoucherMail;
use App\Mail\UserInvitationMail;
use App\Mail\UserPasswordResetMail;
use App\Models\Organisation;
use App\Support\OrganisationContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use ReflectionClass;
use Tests\TestCase;

/**
 * V1.0.36: mail leaves the request, and the organisation goes with it.
 *
 * The hazard worth a test of its own is not that a queued job fails. It
 * is that it succeeds while reading the wrong books: OrganisationScope
 * adds NO filter when nothing is bound, which is right for a console
 * command that must see the whole installation and exactly wrong for a
 * job belonging to one customer. A worker with no organisation bound
 * would read across every organisation there is, quietly.
 *
 * The other half is which mail must not be queued at all. If an MFA code
 * is queued and the send fails, the person is told it was sent, never
 * receives it, and cannot sign in — the failure is invisible at the one
 * moment it locks somebody out.
 */
class QueuedMailTenancyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Somebody is waiting on these and a silent failure locks them out or
     * leaves them guessing, so they are sent while the request is open.
     *
     * @var list<class-string>
     */
    private const INSTANT = [
        MfaCodeMail::class,
        EmailVerificationMail::class,
        UserPasswordResetMail::class,
        UserInvitationMail::class,
        SupportMessageMail::class,

        /*
         * V1.0.48: the email change flow. The two codes hold a person
         * mid-dialog exactly like an MFA code holds a sign-in, and the
         * completion notice is the alarm bell for a takeover — the one
         * mail that must not sit in a queue behind anything.
         */
        \App\Mail\EmailChangeCurrentCodeMail::class,
        \App\Mail\EmailChangeProposedCodeMail::class,
        \App\Mail\EmailChangeCompletedMail::class,

        /*
         * V1.0.50: the confirmation to whoever closed their organisation.
         * The organisation a queued job would bind to no longer exists,
         * and the person is signed out the moment the request returns —
         * it goes in the same breath as the deletion or not at all.
         */
        \App\Mail\OrganisationClosedMail::class,

        /*
         * V1.0.51: the notice to a customer whose organisation platform
         * staff deleted — same reasoning as the closure confirmation.
         */
        \App\Mail\OrganisationDeletedMail::class,
    ];

    /**
     * Synchronous for a different reason, and not a happy one: each of
     * these carries the rendered document as raw bytes, and a queued job
     * is stored as JSON, which binary does not survive. Queueing them
     * means the job carrying an id and rendering the document itself.
     *
     * Kept apart from INSTANT so the two reasons are never confused. One
     * is a decision; the other is unfinished work.
     *
     * @var list<class-string>
     */
    private const CARRIES_A_DOCUMENT = [
        InvoiceMail::class,
        ReceiptMail::class,
        RentReminderMail::class,
        OwnerExpenseBillMail::class,
        OwnerReserveTransferVoucherMail::class,
        TenantFundExpenseVoucherMail::class,
        TenantFundTransferVoucherMail::class,
    ];

    public function test_the_mail_somebody_is_waiting_on_is_never_queued(): void
    {
        foreach (self::INSTANT as $mailable) {
            $this->assertFalse(
                (new ReflectionClass($mailable))->implementsInterface(ShouldQueue::class),
                $mailable.' was queued; a failure to send it is invisible '
                    .'to the person waiting on it'
            );
        }
    }

    public function test_the_mail_nobody_is_waiting_on_is_queued(): void
    {
        $this->assertTrue(
            (new ReflectionClass(RentIncrementNoticeMail::class))
                ->implementsInterface(ShouldQueue::class),
            'the nightly increment run still blocks on Resend once per notice'
        );
    }

    /**
     * A document mailable stays synchronous only while it carries its
     * PDF. The day one is changed to render from an id instead, this
     * fails and asks for it to be queued, so the unfinished work cannot
     * quietly stay unfinished after the reason for it has gone.
     */
    public function test_a_document_mailable_that_no_longer_carries_a_pdf_should_be_queued(): void
    {
        foreach (self::CARRIES_A_DOCUMENT as $mailable) {
            $carries = false;

            foreach ((new ReflectionClass($mailable))->getProperties() as $property) {
                if (str_contains(strtolower($property->getName()), 'pdf')) {
                    $carries = true;
                }
            }

            $this->assertTrue(
                $carries,
                $mailable.' no longer carries a PDF, so the reason it is '
                    .'sent inside the request has gone. Queue it.'
            );
        }
    }

    /**
     * Every mailable is one or the other, deliberately. A new one that is
     * neither has not been thought about.
     */
    public function test_every_mailable_has_been_decided_about(): void
    {
        foreach (glob(app_path('Mail').'/*.php') as $file) {
            $class = 'App\\Mail\\'.basename($file, '.php');

            if (! class_exists($class)) {
                continue;
            }

            $queued = (new ReflectionClass($class))
                ->implementsInterface(ShouldQueue::class);

            $instant = in_array($class, self::INSTANT, true)
                || in_array($class, self::CARRIES_A_DOCUMENT, true);

            $this->assertTrue(
                $queued xor $instant,
                $class.' is neither queued nor on the instant list. Decide '
                    .'which it is: is somebody waiting on it?'
            );
        }
    }

    /**
     * The whole path, for real: push a job with an organisation bound,
     * let go of the binding as a request ending would, then run the
     * worker and ask the job what it could see.
     */
    public function test_the_organisation_travels_into_the_worker(): void
    {
        config(['queue.default' => 'database']);

        $organisation = Organisation::factory()->create();

        /*
         * Not an arrow function. dispatch() returns a PendingDispatch
         * that actually pushes the job when it is DESTROYED, so
         * returning it out of the closure would push the job after
         * runAs had already restored the previous binding — and the
         * payload would name the wrong organisation. Anywhere production
         * code dispatches inside runAs, it must let go of the pending
         * dispatch inside it too.
         */
        OrganisationContext::runAs(
            (int) $organisation->id,
            static function (): void {
                RememberTheBoundOrganisation::dispatch();
            }
        );

        $this->assertSame(
            1,
            DB::table('jobs')->count(),
            'nothing was queued at all'
        );

        OrganisationContext::forget();

        Artisan::call('queue:work', [
            '--once' => true,
            '--stop-when-empty' => true,
        ]);

        $this->assertSame(
            (int) $organisation->id,
            Cache::get('qa.bound-organisation'),
            'the worker ran the job with no organisation bound, so every '
                .'read inside it was unscoped across the whole installation'
        );
    }

    /**
     * And it does not stay bound afterwards, or the next job in the same
     * worker process would inherit it — the same fault the other way up.
     */
    public function test_the_binding_does_not_leak_to_the_next_job(): void
    {
        config(['queue.default' => 'database']);

        $organisation = Organisation::factory()->create();

        OrganisationContext::runAs(
            (int) $organisation->id,
            static function (): void {
                RememberTheBoundOrganisation::dispatch();
            }
        );

        OrganisationContext::forget();

        Artisan::call('queue:work', [
            '--once' => true,
            '--stop-when-empty' => true,
        ]);

        $this->assertNull(
            OrganisationContext::idOrNull(),
            'the worker kept the organisation bound after the job finished'
        );
    }
}

/**
 * Writes down which organisation it could see. Nothing else.
 */
class RememberTheBoundOrganisation implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(): void
    {
        Cache::put(
            'qa.bound-organisation',
            OrganisationContext::idOrNull()
        );
    }
}
