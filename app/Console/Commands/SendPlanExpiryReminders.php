<?php

namespace App\Console\Commands;

use App\Mail\PlanExpiryReminderMail;
use App\Models\License;
use App\Models\Organisation;
use App\Models\User;
use App\Services\LicensingService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Warn customer administrators 7 days and 1 day before their trial or
 * licence ends.
 *
 * The daily run matches exact day distances, so each threshold fires
 * exactly once. Platform service mail: never counted against email
 * allowances, sent in each organisation's configured language.
 */
#[Signature('patrimoine:send-plan-expiry-reminders {--as-of= : Process reminders as of YYYY-MM-DD}')]
#[Description('Email customer administrators before their trial or licence expires')]
class SendPlanExpiryReminders extends Command
{
    /**
     * Days-before-expiry thresholds that trigger a reminder.
     *
     * @var list<int>
     */
    private const THRESHOLDS = [7, 1];

    /**
     * Execute the reminder run.
     */
    public function handle(
        LicensingService $licensing
    ): int {
        $asOf = now()->startOfDay();

        $option = trim((string) $this->option('as-of'));

        if ($option !== '') {
            $asOf = \Carbon\Carbon::createFromFormat('Y-m-d', $option)
                ->startOfDay();
        }

        $sent = 0;
        $failed = 0;

        $customers = Organisation::query()
            ->customers()
            ->where('status', 'active')
            ->with('licenses')
            ->get();

        foreach ($customers as $organisation) {
            foreach (self::THRESHOLDS as $days) {
                $target = $asOf->copy()->addDays($days)->toDateString();

                try {
                    /*
                     * A running trial ending on the target date...
                     */
                    if (
                        $licensing->onTrialFor($organisation)
                        && $organisation->trial_ends_on?->toDateString() === $target
                    ) {
                        $this->remind(
                            $organisation,
                            'trial',
                            (string) config('licensing.trial_plan'),
                            $target,
                            $days
                        );

                        $sent++;

                        continue;
                    }

                    /*
                     * ...or the currently covering licence ending then.
                     */
                    $covering = $organisation->licenses->first(
                        fn (License $license): bool => $license->coversToday()
                    );

                    if (
                        $covering !== null
                        && $covering->expires_on?->toDateString() === $target
                    ) {
                        $this->remind(
                            $organisation,
                            'license',
                            $covering->plan,
                            $target,
                            $days
                        );

                        $sent++;
                    }
                } catch (Throwable $exception) {
                    $failed++;

                    report($exception);

                    $this->error(sprintf(
                        'Organisation #%d failed: %s',
                        $organisation->id,
                        $exception->getMessage()
                    ));
                }
            }
        }

        $this->info(sprintf(
            'Expiry reminders: %d sent, %d failed.',
            $sent,
            $failed
        ));

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Email every active, verified administrator of the organisation.
     */
    private function remind(
        Organisation $organisation,
        string $kind,
        string $plan,
        string $endsOn,
        int $daysLeft
    ): void {
        $language = (string) (
            DB::table('application_settings')
                ->where('organisation_id', $organisation->id)
                ->value('language')
            ?? 'en'
        );

        $administrators = User::withoutGlobalScopes()
            ->where('organisation_id', $organisation->id)
            ->where('role', 'administrator')
            ->where('is_active', true)
            ->whereNotNull('email_verified_at')
            ->get();

        foreach ($administrators as $administrator) {
            Mail::to($administrator->email)
                ->locale($language)
                ->send(
                    new PlanExpiryReminderMail(
                        user: $administrator,
                        organisationName: $organisation->name,
                        kind: $kind,
                        plan: $plan,
                        endsOn: $endsOn,
                        daysLeft: $daysLeft
                    )
                );
        }

        $this->line(sprintf(
            'Organisation #%d (%s): %s ends %s — %d-day reminder sent.',
            $organisation->id,
            $organisation->name,
            $kind,
            $endsOn,
            $daysLeft
        ));
    }
}
