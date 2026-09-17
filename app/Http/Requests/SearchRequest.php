<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // puedes luego poner lógica de permisos
    }

    public function rules(): array
    {
        return [
            'search'     => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
        ];
    }

    public function hasNoFilters(): bool
    {
        return collect($this->validated())
            ->filter()
            ->isEmpty();
    }
}
