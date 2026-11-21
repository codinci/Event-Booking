<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && ($user->isOrganizer() || $user->isAdmin());
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'title' => [
                'required',
                'string',
                'max:255',
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'venue' => [
                'required',
                'string',
                'max:255',
            ],
            'location' => [
                'nullable',
                'string',
                'max:255',
            ],
            'image_url' => [
                'nullable',
                'url',
                'starts_with:https://',
                'max:255',
            ],
            'starts_at' => [
                'required',
                'date',
            ],
            'ends_at' => [
                'required',
                'date',
                'after:starts_at',
            ],
            'ticket_types' => [
                'required',
                'array',
                'min:1',
            ],
            'ticket_types.*.name' => [
                'required',
                'string',
                'max:255',
            ],
            'ticket_types.*.price' => [
                'required',
                'numeric',
                'min:0',
            ],
            'ticket_types.*.quantity' => [
                'required',
                'integer',
                'min:1',
            ],
        ];
    }
}
