<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // pelanggan tidak perlu login
    }

    public function rules(): array
    {
        return [
            'table_number' => ['nullable', 'integer', 'min:1', 'max:99', Rule::exists('restaurant_tables', 'number')->where('is_active', true)],
            'customer_name' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'table_number.exists' => 'Nomor meja tidak ditemukan.',
            'items.required' => 'Pilih minimal satu menu sebelum mengirim pesanan.',
            'items.min' => 'Pilih minimal satu menu sebelum mengirim pesanan.',
            'items.*.id.exists' => 'Ada menu yang sudah tidak tersedia. Muat ulang halaman.',
            'items.*.quantity.max' => 'Jumlah per menu maksimal 20 porsi.',
        ];
    }
}
