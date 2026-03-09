<?php

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
        Schema::table('empreendimentos', function (Blueprint $table) {
            $table->string('map_type')->default('map')->after('map_zoom'); // 'map' | 'image'
            $table->string('map_image')->nullable()->after('map_type');
            $table->unsignedInteger('map_image_width')->nullable()->after('map_image');
            $table->unsignedInteger('map_image_height')->nullable()->after('map_image_width');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empreendimentos', function (Blueprint $table) {
            $table->dropColumn(['map_type', 'map_image', 'map_image_width', 'map_image_height']);
        });
    }
};
