<?php

use App\Models\Page;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('layout', 20)->default('standard'); // standard | faq
            $table->string('intro', 500)->nullable();
            $table->longText('body')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now = now();
        foreach (Page::DEFAULTS as [$slug, $title, $layout, $intro, $body]) {
            DB::table('pages')->insert(compact('slug', 'title', 'layout', 'intro', 'body') + [
                'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
