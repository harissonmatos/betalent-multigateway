<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client.name' => 'required|string',
            'client.email' => 'required|email',

            'payment.cardNumber' => 'required|string|min:13|max:19',
            'payment.cvv' => 'required|string|min:3|max:4',
            'payment.expiry' => 'required|string', // formato MM/YY ou MM/YYYY

            'products' => 'required|array|min:1',
            'products.*.id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'products.*.id.exists' => 'Produto não encontrado.',
            'products.required' => 'Informe pelo menos um produto.',
            'products.*.quantity.min' => 'Quantidade deve ser maior que zero.',
        ];
    }
}
