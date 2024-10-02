<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\AssetType;
use App\Jobs\DeleteFile;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileStoreRequest extends FormRequest
{
    /**
     * Move the file to the correct path.
     */
    public function getNewPath(AssetType $assetType, ?Model $for = null): string
    {
        $prependWith = $assetType->toStoragePath() . '/';

        if ($for) {
            $modelType = array_flip(Relation::morphMap())[$for::class];

            $prependWith = $modelType . '/' . $for->getRouteKey() . '/' . $prependWith;
        }

        // todo add random string
        $newPath = Str::of($this->validated('key'))->after('tmp/');

        return $newPath
            ->beforeLast('.')
            ->slug()
            ->prepend($prependWith)
            ->append('.', Str::afterLast($this->validated('key'), '.'))
            ->toString();
    }

    /**
     * Common validation rules for the key attribute.
     */
    protected function keyValidationRules(): array
    {
        return [
            'required',
            'string',
            'starts_with:tmp/',
            function (string $attribute, string $value, $fail): void {
                if (!Storage::exists($value)) {
                    $fail('The file doesn\'t exists at this path.');
                }
            }
        ];
    }

    /**
     * If the validation fails, delete the file from the storage.
     * This only happens if the user is tinkering with the request or the frontend code.
     *
     * {@inheritdoc}
     */
    #[\Override]
    protected function failedValidation(Validator $validator): void
    {
        $key = $this->string('key');

        if ($key->isNotEmpty() && $key->startsWith('tmp/')) {
            DeleteFile::dispatch($this->input('key'));
        }

        parent::failedValidation($validator);
    }
}
