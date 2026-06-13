<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\SlaStatus;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexTicketRequest extends FormRequest
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
            'status' => ['sometimes', Rule::enum(TicketStatus::class)],
            'priority' => ['sometimes', Rule::enum(TicketPriority::class)],
            'sla_status' => ['sometimes', Rule::enum(SlaStatus::class)],
            'organization_id' => ['sometimes', 'integer', 'exists:organizations,id'],
            'assigned_to' => ['sometimes', 'integer', 'exists:users,id'],
            'unassigned' => ['sometimes', 'boolean'],
            'search' => ['sometimes', 'string', 'max:255'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
