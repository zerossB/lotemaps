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
        Schema::table('lots', function (Blueprint $table) {
            $table->foreignId('empreendimento_id')
                ->nullable()
                ->after('id')
                ->constrained('empreendimentos')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('lots', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\Empreendimento::class);
            $table->dropColumn('empreendimento_id');
        });
    }
};
