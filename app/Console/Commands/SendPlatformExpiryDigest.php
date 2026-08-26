<?php

namespace App\Console\Commands;

use App\Mail\PlatformExpiryDigestMail;
use App\Models\License;
use App\Models\Organisation;
use App\Services\LicensingService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Weekly digest to billing@: every customer trial and licence ending
 * within the next 14 days. Quiet when there is nothing to report.
 */
#[Signature('patrimoine:send-platform-expiry-digest')]
#[Description('Email billing@ the trials and licences expiring within 14 days')]
class SendPlatformExpiryDigest extends Command
{
    /**
     * Execute the digest run.
     */
    public function handle(
        LicensingService $licensing
    ): int {
        $horizon = now()->addDays(14)->toDateString();

        $rows = [];

        $customers = Organisation::query()
            ->customers()
            ->where('status', 'active')
            ->with('licenses')
            ->get();

        foreach ($customers as $organisation) {
            if (
                $licensing->onTrialFor($organisation)
                && $organisation->trial_ends_on !== null
                && $organisation->trial_ends_on->toDateString() <= $horizon
            ) {
                $rows[] = [
                    'organisation' => $organisation->name,
                    'kind' => 'trial',
                    'plan' => (string) config('licensing.trial_plan'),
                    'ends_on' => $organisation->trial_ends_on->toDateString(),
                ];

                continue;
            }

            $covering = $organisation->licenses->first(
                fn (License $license): bool => $license->coversToday()
            );

            if (
                $covering !== null
                && $covering->expires_on !== null
                && $covering->expires_on->toDateString() <= $horizon
            ) {
                $rows[] = [
                    'organisation' => $organisation->name,
                    'kind' => 'license',
                    'plan' => $covering->plan,
                    'ends_on' => $covering->expires_on->toDateString(),
                ];
            }
        }

        if ($rows === []) {
            $this->info('Nothing expiring within 14 days.');

            return self::SUCCESS;
        }

        usort(
            $rows,
            fn (array $a, array $b): int =>
                strcmp($a['ends_on'], $b['ends_on'])
        );

        Mail::to(
            (string) config('legal.mailboxes.billing')
        )->send(
            new PlatformExpiryDigestMail(rows: $rows)
        );

        $this->info(sprintf(
            'Digest sent: %d expiring plan(s).',
            count($rows)
        ));

        return self::SUCCESS;
    }
}
