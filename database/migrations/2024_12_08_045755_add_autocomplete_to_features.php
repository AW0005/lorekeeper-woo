<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAutocompleteToFeatures extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::table('features', function (Blueprint $table) {
            $table->boolean('extras_autocomplete')->default(false);
            $table->boolean('extras_list')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('features', function (Blueprint $table) {
            $table->dropColumn('extras_autocomplete');
            $table->dropColumn('extras_list');
        });
    }
}
