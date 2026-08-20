<?php

namespace App\Http\Requests;

use App\Services\LeaseTermination\LeaseTerminationStateService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InitiateLeaseTerminationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'notice_date' => [
                'required',
                'date',
            ],

            'termination_date' => [
                'required',
                'date',
            ],

            'final_rent_mode' => [
                'required',
                Rule::in([
                    LeaseTerminationStateService::FINAL_RENT_PRORATE,
                    LeaseTerminationStateService::FINAL_RENT_FULL,
                    LeaseTerminationStateService::FINAL_RENT_NONE,
                ]),
            ],
        ];
    }

    public function after(): array
    {
        return [
            function ($validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $lease = $this->route('lease');

                if ($lease === null) {
                    return;
                }

                if ($lease->status !== 'active') {
                    $validator->errors()->add(
                        'lease',
                        'Only an active Lease can enter the termination workflow.'
                    );

                    return;
                }

                $noticeDate = CarbonImmutable::parse(
                    $this->input('notice_date')
                )->startOfDay();

                $terminationDate = CarbonImmutable::parse(
                    $this->input('termination_date')
                )->startOfDay();

                $startDate = CarbonImmutable::parse(
                    $lease->start_date
                )->startOfDay();

                if ($noticeDate->lt($startDate)) {
                    $validator->errors()->add(
                        'notice_date',
                        'The termination notice date cannot be before the Lease start date.'
                    );
                }

                if ($terminationDate->lt($noticeDate)) {
                    $validator->errors()->add(
                        'termination_date',
                        'The termination date cannot be before the notice date.'
                    );
                }
            },
        ];
    }
}
