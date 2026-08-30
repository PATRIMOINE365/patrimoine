<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * V1.0.36: a message to support, written from inside the application.
 *
 * Only the subject and the message are typed. Who is writing, which
 * organisation they belong to and how to answer them are all read from
 * the session — a support request that has to be introduced by hand is
 * one a customer can get wrong, and an address typed into a form is one
 * anybody could put somebody else's name against.
 */
class StoreSupportMessageRequest extends FormRequest
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
            'subject' => [
                'required',
                'string',
                'max:150',
            ],

            'message' => [
                'required',
                'string',
                'max:5000',
            ],
        ];
    }
}
