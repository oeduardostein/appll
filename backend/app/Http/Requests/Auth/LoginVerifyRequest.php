<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class LoginVerifyRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('challenge_id')) {
            $this->merge([
                'challenge_id' => trim((string) $this->input('challenge_id')),
            ]);
        }

        if ($this->has('security_key')) {
            $this->merge([
                'security_key' => trim((string) $this->input('security_key')),
            ]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'challenge_id' => ['required', 'string', 'min:20', 'max:120'],
            'security_key' => ['required', 'string', 'min:4', 'max:20'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'status' => 'error',
            'message' => 'Os dados fornecidos são inválidos.',
            'errors' => $validator->errors(),
        ], 422));
    }
}

