<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreRestaurantTableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'number' => ['required', 'integer', 'min:1', 'max:99', 'unique:restaurant_tables,number'],
        ];
    }

    public function messages(): array
    {
        return [
            'number.unique' => 'Nomor meja tersebut sudah terdaftar.',
        ];
    }
}
