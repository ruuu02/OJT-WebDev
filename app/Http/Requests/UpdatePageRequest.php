<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // already protected by 'auth' middleware on the route
    }

    public function rules(): array
    {
        return [
            'title'        => ['required', 'string', 'max:255'],
            'hero_heading' => ['nullable', 'string', 'max:255'],
            'hero_subtext' => ['nullable', 'string'],
            'sections'     => ['nullable', 'array'],
        ];
    }
}