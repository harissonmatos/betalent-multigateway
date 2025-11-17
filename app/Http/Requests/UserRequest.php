<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // controle de permissão fica no middleware/controller
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'name' => $this->isMethod('post') ? 'required' : 'nullable',
            'email' => 'required|email|unique:users,email,'.$userId,
            'password' => $this->isMethod('post') ? 'required|min:6' : 'nullable|min:6',
            'role' => 'required|in:ADMIN,MANAGER,FINANCE,USER',
        ];
    }
}
