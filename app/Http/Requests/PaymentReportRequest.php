<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PaymentReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'from' => [
                'nullable',
                'date_format:Y-m-d',
            ],

            'to' => [
                'nullable',
                'date_format:Y-m-d',
            ],

            'tenant_id' => [
                'nullable',
                'integer',
                'exists:parties,id',
            ],

            'lease_id' => [
                'nullable',
                'integer',
                'exists:leases,id',
            ],

            'building_id' => [
                'nullable',
                'integer',
                'exists:buildings,id',
            ],

            'unit_id' => [
                'nullable',
                'integer',
                'exists:units,id',
            ],

            'payment_method' => [
                'nullable',
                Rule::in([
                    'cash',
                    'bank_transfer',
                    'momo',
                ]),
            ],

            'cash_receiver' => [
                'nullable',
                'string',
                'max:255',
            ],

            'reference' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $from = $this->input('from');
                $to = $this->input('to');

                if (
                    is_string($from)
                    && is_string($to)
                    && $from > $to
                ) {
                    $validator->errors()->add(
                        'to',
                        __('ui.reports.invalid_period')
                    );
                }
            },
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        return $this->validated();
    }
}
