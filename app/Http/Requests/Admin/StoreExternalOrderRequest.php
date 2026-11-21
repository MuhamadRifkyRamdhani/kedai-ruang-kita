<?php

namespace App\Http\Requests\Admin;

use App\Enums\OrderSource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Form "Catat pesanan marketplace". Nama field mengikuti form yang sudah ada: source, code, total, notes. */
class StoreExternalOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'source' => ['required', Rule::in(OrderSource::marketplaceValues())],
            // "code" di form = ID pesanan di platform; disimpan ke kolom platform_order_id.
            'code' => [
                'nullable', 'string', 'max:80',
                Rule::unique('orders', 'platform_order_id')->where('source', $this->input('source')),
            ],
            'total' => ['required', 'integer', 'min:1000'],
            'notes' => ['nullable', 'string', 'max:300'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique' => 'ID pesanan platform ini sudah pernah dicatat.',
        ];
    }
}
