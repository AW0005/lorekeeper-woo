<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRefImages extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ref_images', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');

            // relation to a form
            $table->integer('image_id')->unsigned();

            // Storing the image
            $table->string('hash', 10)->nullable();
            $table->string('extension', 5)->nullable();
            $table->string('fullsize_hash', 20)->nullable();

            // Display order
            $table->integer('sort')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('image_id')->references('id')->on('character_images');
        });

        DB::statement("ALTER TABLE character_image_creators MODIFY character_type ENUM('Update', 'Character', 'RefImage')");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ref_images');
        DB::statement("ALTER TABLE character_image_creators MODIFY character_type ENUM('Update', 'Character')");
    }
}
