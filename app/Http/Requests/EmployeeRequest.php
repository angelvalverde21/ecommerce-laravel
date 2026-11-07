<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['master', 'ceo', 'admin']) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $values = [];
        foreach (['name', 'email', 'account_email', 'phone', 'document_number', 'biometric_code', 'tag_sales'] as $field) {
            if ($this->exists($field)) {
                $value = trim((string) $this->input($field));
                $values[$field] = $value === '' ? null : $value;
            }
        }
        foreach (['work_time_start', 'work_time_end'] as $field) {
            if (preg_match('/^\d{2}:\d{2}$/', (string) $this->input($field))) {
                $values[$field] = $this->input($field).':00';
            }
        }
        $this->merge($values);
        // Accept the previous account-email field while older clients are updated.
        if ($this->exists('account_email')) {
            $this->merge(['email' => $this->input('account_email')]);
        }
    }

    public function rules(): array
    {
        $store = $this->route('store');
        $id = $this->route('employee_id');
        $employee = $id ? $store->employees()->findOrFail($id) : null;
        $creatingAccount = ! $employee?->user_id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [$creatingAccount ? 'required' : 'sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($employee?->user_id)],
            'password' => [$creatingAccount ? 'required' : 'nullable', 'string', 'min:8', 'max:72', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:30'],
            'identity_id' => ['nullable', 'integer', 'exists:identities,id'],
            'document_number' => ['nullable', 'string', 'max:50', Rule::unique('users', 'document_number')
                ->where(fn ($query) => $query->whereIn('id', DB::table('employees')->select('user_id')->where('store_id', $store->id)))
                ->ignore($employee?->user_id)],
            'biometric_code' => ['nullable', 'string', 'max:50', Rule::unique('employees')->where('store_id', $store->id)->ignore($id)],
            'status' => ['sometimes', 'boolean'],
            'roles' => [$creatingAccount ? 'required' : 'sometimes', 'array', 'min:1'],
            'roles.*' => ['string', 'distinct', Rule::exists('roles', 'name')->where('guard_name', 'api')],
            'salary' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'type' => ['nullable', Rule::in(['fulltime', 'partime'])],
            'comission' => ['nullable', 'integer', 'between:0,100'],
            'tag_sales' => ['nullable', 'string', 'max:100'],
            'date_entry' => ['nullable', 'date_format:Y-m-d'],
            'work_time_start' => ['nullable', 'date_format:H:i:s'],
            'work_time_end' => ['nullable', 'date_format:H:i:s', 'after:work_time_start'],
            'tolerance_minutes' => ['sometimes', 'integer', 'between:0,120'],
            'allow_extra_hours' => ['sometimes', 'boolean'],
        ];
    }
}
