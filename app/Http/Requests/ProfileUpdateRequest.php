<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    public function prepareForValidation(): void
    {
        $username = Str::of((string) $this->input('username'))
            ->trim()
            ->lower()
            ->value();

        $bio = $this->input('bio');
        $bio = is_string($bio) ? trim($bio) : $bio;

        $this->merge([
            'name' => trim((string) $this->input('name')),
            'username' => $username,
            'email' => trim((string) $this->input('email')),
            'bio' => $bio !== '' ? $bio : null,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'min:3',
                'max:30',
                'regex:/^[a-z0-9_]+$/',
                Rule::unique(User::class, 'username')->ignore($this->user()->id),
            ],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'not_regex:/[\\r\\n]/',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'bio' => ['nullable', 'string', 'max:280'],
        ];
    }
}
