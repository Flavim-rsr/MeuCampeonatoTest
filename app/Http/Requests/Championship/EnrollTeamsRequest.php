<?php

namespace App\Http\Requests\Championship;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EnrollTeamsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'team_ids' => ['required', 'array', 'min:1', 'max:8'],
            'team_ids.*' => [
                'integer',
                Rule::exists('teams', 'id')->where('user_id', $this->user()?->getAuthIdentifier()),
            ],
        ];
    }
}
