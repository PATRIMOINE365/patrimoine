<?php

namespace App\Services\Accounting;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use RuntimeException;

/**
 * Stable Phase 2 boundary around the Phase 1
 * OpeningBalancePostingService.
 *
 * The Phase 1 service was intentionally developed as accounting
 * infrastructure before the deployment/cutover workflow existed.
 * This adapter isolates the cutover workflow from that lower-level
 * API and keeps future cutover logic stable.
 */
class OpeningBalancePostingAdapter
{
    public function __construct(
        private readonly OpeningBalancePostingService $posting
    ) {
    }

    /**
     * @param array<int,array<string,mixed>> $positions
     */
    public function post(
        array $positions,
        CarbonImmutable $cutoverDate,
        ?User $actor = null
    ): mixed {
        $method =
            $this->resolvePostingMethod();

        $arguments =
            $this->buildArguments(
                $method,
                $positions,
                $cutoverDate,
                $actor
            );

        return $method->invokeArgs(
            $this->posting,
            $arguments
        );
    }

    private function resolvePostingMethod(): ReflectionMethod
    {
        $reflection =
            new ReflectionClass(
                $this->posting
            );

        $preferred = [
            'post',
            'postOpeningBalances',
            'postOpeningBalance',
            'postPositions',
            'postOpeningPositions',
        ];

        foreach ($preferred as $name) {
            if (
                ! $reflection->hasMethod(
                    $name
                )
            ) {
                continue;
            }

            $method =
                $reflection->getMethod(
                    $name
                );

            if ($method->isPublic()) {
                return $method;
            }
        }

        /*
         * Fall back only when exactly one application-defined public
         * posting method exists.
         */
        $candidates =
            collect(
                $reflection->getMethods(
                    ReflectionMethod::IS_PUBLIC
                )
            )
                ->filter(
                    fn (
                        ReflectionMethod $method
                    ): bool =>
                        $method->class
                        === $reflection->getName()
                        && $method->name
                        !== '__construct'
                )
                ->values();

        if ($candidates->count() === 1) {
            return $candidates->first();
        }

        throw new RuntimeException(
            sprintf(
                'Unable to resolve OpeningBalancePostingService public posting method. Found: %s',
                $candidates
                    ->pluck('name')
                    ->implode(', ')
            )
        );
    }

    /**
     * @param array<int,array<string,mixed>> $positions
     * @return array<int,mixed>
     */
    private function buildArguments(
        ReflectionMethod $method,
        array $positions,
        CarbonImmutable $cutoverDate,
        ?User $actor
    ): array {
        $arguments = [];

        foreach (
            $method->getParameters()
            as $parameter
        ) {
            $name =
                $parameter->getName();

            $normalized =
                strtolower($name);

            if (
                in_array(
                    $normalized,
                    [
                        'positions',
                        'openingpositions',
                        'opening_positions',
                        'balances',
                        'openingbalances',
                        'opening_balances',
                    ],
                    true
                )
            ) {
                $arguments[] =
                    $positions;

                continue;
            }

            if (
                in_array(
                    $normalized,
                    [
                        'date',
                        'journaldate',
                        'journal_date',
                        'openingdate',
                        'opening_date',
                        'cutoverdate',
                        'cutover_date',
                    ],
                    true
                )
            ) {
                $arguments[] =
                    $this->dateArgument(
                        $parameter
                            ->getType(),
                        $cutoverDate
                    );

                continue;
            }

            if (
                in_array(
                    $normalized,
                    [
                        'description',
                        'memo',
                        'label',
                    ],
                    true
                )
            ) {
                $arguments[] =
                    'V1.0.5 Opening Balance';

                continue;
            }

            if (
                in_array(
                    $normalized,
                    [
                        'actor',
                        'user',
                        'actoruser',
                        'actor_user',
                    ],
                    true
                )
            ) {
                $arguments[] =
                    $actor;

                continue;
            }

            if (
                $parameter
                    ->isDefaultValueAvailable()
            ) {
                $arguments[] =
                    $parameter
                        ->getDefaultValue();

                continue;
            }

            throw new RuntimeException(
                sprintf(
                    'Unsupported required parameter $%s on OpeningBalancePostingService::%s().',
                    $name,
                    $method->getName()
                )
            );
        }

        return $arguments;
    }

    private function dateArgument(
        mixed $type,
        CarbonImmutable $date
    ): mixed {
        if (
            ! $type
            instanceof ReflectionNamedType
        ) {
            return $date;
        }

        return match (
            $type->getName()
        ) {
            'string' =>
                $date->toDateString(),

            default =>
                $date,
        };
    }
}
