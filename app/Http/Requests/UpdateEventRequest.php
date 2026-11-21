<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEventRequest extends FormRequest
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
                'sometimes',
                'array',
                'min:1',
            ],
            'ticket_types.*.id' => [
                'integer',
            ],
            'ticket_types.*.name' => [
                'required_with:ticket_types',
                'string',
                'max:255',
            ],
            'ticket_types.*.price' => [
                'required_with:ticket_types',
                'numeric',
                'min:0',
            ],
            'ticket_types.*.quantity' => [
                'required_with:ticket_types',
                'integer',
                'min:1',
            ],
        ];
    }
}
