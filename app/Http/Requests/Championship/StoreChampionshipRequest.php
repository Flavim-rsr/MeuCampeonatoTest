<?php

namespace App\Http\Requests\Championship;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreChampionshipRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:120'],
            'tiebreaker_mode' => ['sometimes', Rule::in(['default', 'penalties'])],
        ];
    }

    /**
     * Default `tiebreaker_mode` to "default" when the caller omits it.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->has('tiebreaker_mode')) {
            $this->merge(['tiebreaker_mode' => 'default']);
        }
    }
}
