<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AiPromptRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'prompt' => ['required', 'string', 'max:2000'],
            'conversation_id' => ['nullable', 'string', 'max:36'],
        ];
    }
}
