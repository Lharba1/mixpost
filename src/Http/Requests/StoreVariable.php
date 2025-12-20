<?php

namespace Inovector\Mixpost\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVariable extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'key' => 'required|string|max:255|alpha_dash|unique:mixpost_variables,key',
            'value' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'key.alpha_dash' => 'The variable key may only contain letters, numbers, dashes, and underscores.',
            'key.unique' => 'This variable key already exists.',
        ];
    }
}
