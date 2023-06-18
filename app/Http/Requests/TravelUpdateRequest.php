<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TravelUpdateRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        /** @var Request $route */
        $route = $this->route('travel');

        return [
            'is_public' => 'boolean',
            'name' => ['required', Rule::unique('travels')->ignore($route->id)],
            'description' => ['required'],
            'number_of_days' => ['required', 'integer'],
        ];
    }
}
