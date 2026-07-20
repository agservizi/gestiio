<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('openstamanager_documents');
    }

    public function down(): void
    {
        // Tabella OSM rimossa definitivamente dal progetto.
    }
};
