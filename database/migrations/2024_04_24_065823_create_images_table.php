<?php

declare(strict_types=1);

use App\Models\Image;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('images', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('width');
            $table->unsignedInteger('height');
            $table->unsignedInteger('size');
            $table->string('mime_type');
            $table->string('title');
            $table->string('storage_location');
            $table->timestamps();
        });

        Schema::create('imageables', static function (Blueprint $table): void {
            $table->foreignIdFor(Image::class)->constrained()->restrictOnDelete();
            $table->morphs('imageable');
            $table->jsonb('meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('imageables');
        Schema::dropIfExists('images');
    }
};
