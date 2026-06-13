<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\TicketPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TicketRequest extends FormRequest
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
        $priorities = array_column(TicketPriority::cases(), 'value');

        if ($this->isUpdate()) {
            return [
                'subject' => ['sometimes', 'string', 'max:255'],
                'description' => ['sometimes', 'string'],
                'priority' => ['sometimes', Rule::in($priorities)],
            ];
        }

        $rules = [
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'priority' => ['required', Rule::in($priorities)],
        ];

        if ($this->user()?->isAgent()) {
            $rules['organization_id'] = ['required', 'integer', 'exists:organizations,id'];
        }

        return $rules;
    }

    private function isUpdate(): bool
    {
        return $this->isMethod('PUT') || $this->isMethod('PATCH');
    }
}
