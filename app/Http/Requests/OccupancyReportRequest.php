<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OccupancyReportRequest extends FormRequest
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
            'as_of' => [
                'nullable',
                'date_format:Y-m-d',
            ],
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
