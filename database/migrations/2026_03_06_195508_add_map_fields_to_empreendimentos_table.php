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
            $table->decimal('map_lat', 10, 7)->nullable()->after('status');
            $table->decimal('map_lng', 10, 7)->nullable()->after('map_lat');
            $table->tinyInteger('map_zoom')->nullable()->after('map_lng');
        });
    }

    public function down(): void
    {
        Schema::table('empreendimentos', function (Blueprint $table) {
            $table->dropColumn(['map_lat', 'map_lng', 'map_zoom']);
        });
    }
};
