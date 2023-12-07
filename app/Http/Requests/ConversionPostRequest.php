<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConversionPostRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|gt:0',
            'from'   => ['required', 'regex:/^[A-Z]{3}$/'],
            'to'     => ['required', 'regex:/^[A-Z]{3}$/'],
        ];
    }
}
