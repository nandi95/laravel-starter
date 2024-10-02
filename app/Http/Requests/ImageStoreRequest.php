<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;

class ImageStoreRequest extends FileStoreRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    #[\Override]
    public function rules(): array
    {
        return [
            'size' => ['required', 'integer'],
            'width' => ['required', 'integer'],
            'height' => ['required', 'integer'],
            'title' => ['sometimes', 'string', 'nullable', 'max:255'],
            'key' => $this->keyValidationRules()
        ];
    }
}
