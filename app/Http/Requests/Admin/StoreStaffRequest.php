<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // dibatasi middleware role:admin di route
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:60', 'unique:users,name'],
            'password' => ['required', 'string', 'min:8', 'max:100'],
            'role' => ['required', Rule::enum(UserRole::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'Nama tersebut sudah dipakai staf lain.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
        ];
    }
}
