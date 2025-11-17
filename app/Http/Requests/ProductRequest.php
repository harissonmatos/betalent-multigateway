<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => $this->isMethod('post') ? 'required' : 'nullable',
            'amount' => $this->isMethod('post')
                ? 'required|numeric|min:0'
                : 'nullable|numeric|min:0',
        ];
    }
}
