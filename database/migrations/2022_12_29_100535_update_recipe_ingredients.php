<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::table('recipe_ingredients', function (Blueprint $table) {
            DB::statement("ALTER TABLE recipe_ingredients MODIFY COLUMN ingredient_type ENUM('Item', 'MultiItem', 'Category', 'MultiCategory', 'Currency', 'Pet', 'MultiPet')");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('recipe_ingredients', function (Blueprint $table) {
            DB::statement("ALTER TABLE recipe_ingredients MODIFY COLUMN ingredient_type ENUM('Item', 'MultiItem', 'Category', 'MultiCategory', 'Currency')");
        });
    }
};
