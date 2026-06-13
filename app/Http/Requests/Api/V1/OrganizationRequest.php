<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrganizationRequest extends FormRequest
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
        if ($this->isUpdate()) {
            $id = $this->route('organization');

            return [
                'name' => ['sometimes', 'string', 'max:255'],
                'slug' => ['sometimes', 'string', 'max:255', Rule::unique('organizations', 'slug')->ignore($id)],
                'is_active' => ['sometimes', 'boolean'],
            ];
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:organizations,slug'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    private function isUpdate(): bool
    {
        return $this->isMethod('PUT') || $this->isMethod('PATCH');
    }
}
