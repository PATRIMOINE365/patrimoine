<?php

namespace App\Providers;

use App\Services\ApplicationLocaleService;
use App\Support\OrganisationContext;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;
use Throwable;

/**
 * V1.0.36: carry the organisation into the queue worker.
 *
 * This is the thing that has to be right before a single email is
 * queued, and it is not obvious, so it is written down here rather than
 * discovered later.
 *
 * Patrimoine is multi-tenant by a bound organisation. Every request binds
 * one, and OrganisationScope filters every read to it. But read the scope
 * carefully: when NOTHING is bound it adds no filter at all. That is the
 * right behaviour for a console command that must see the whole
 * installation, and it is exactly the wrong behaviour for a job that
 * belongs to one customer. A queued job with no organisation bound does
 * not fail — it quietly reads across every organisation there is.
 *
 * So the organisation is stamped into the payload when the job is pushed
 * and bound again while it runs. One place, applying to every queued job
 * now and every one added later, rather than each job remembering to do
 * it for itself.
 *
 * The language travels with it. Patrimoine deliberately ignores
 * Accept-Language and reads the organisation's own setting instead, which
 * a worker has no request to resolve from. Binding the organisation and
 * then applying its language is what stops a French customer being sent
 * an English invoice from the queue while the same invoice sent from the
 * screen arrives in French.
 */
class QueueTenancyServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        /*
         * Stamped at push time, from whatever is bound then — which is
         * the organisation whose work caused the job.
         *
         * One trap worth knowing before dispatching anything from inside
         * OrganisationContext::runAs(): Job::dispatch() returns a
         * PendingDispatch that pushes when it is DESTROYED, not when it
         * is called. Let go of it inside the closure. Returning it out
         * of an arrow function pushes the job after runAs has restored
         * the previous binding, and the payload then names the wrong
         * organisation — silently, and only under a nested binding.
         */
        Queue::createPayloadUsing(
            static fn (): array => [
                'patrimoine_organisation_id' => OrganisationContext::idOrNull(),
            ]
        );

        Queue::before(function (JobProcessing $event): void {
            $this->bindFromPayload($event->job->payload());
        });

        /*
         * Released whichever way the job ends. A worker handles many
         * jobs in one process and a binding left behind would be
         * inherited by the next one, which is the same fault in the
         * opposite direction.
         */
        Queue::after(
            static fn (JobProcessed $event) => OrganisationContext::forget()
        );

        Queue::failing(
            static fn (JobFailed $event) => OrganisationContext::forget()
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function bindFromPayload(array $payload): void
    {
        OrganisationContext::forget();

        $organisationId = $payload['patrimoine_organisation_id'] ?? null;

        if (! is_int($organisationId) && ! ctype_digit((string) $organisationId)) {
            return;
        }

        OrganisationContext::bind((int) $organisationId);

        /*
         * Language is read from the organisation that was just bound, so
         * this must follow the bind. A failure here must not cost the
         * job: the default language is a worse email than the right one,
         * but no email at all is worse than both.
         */
        try {
            app(ApplicationLocaleService::class)->applyLanguage();
        } catch (Throwable) {
            app()->setLocale(
                (string) config('patrimoine.defaults.language', 'en')
            );
        }
    }
}
