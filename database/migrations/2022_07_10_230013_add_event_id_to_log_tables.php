<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEventIdToLogTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::create('log_events', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('event_type')->nullable();

            $table->timestamps();
        });

        Schema::table('admin_log', function (Blueprint $table) {
            $table->integer('event_id')->nullable()->unsigned();
            $table->foreign('event_id')->nullable()->references('id')->on('log_events');
        });

        Schema::table('awards_log', function (Blueprint $table) {
            $table->integer('event_id')->nullable()->unsigned();
            $table->foreign('event_id')->nullable()->references('id')->on('log_events');
        });

        Schema::table('character_log', function (Blueprint $table) {
            $table->integer('event_id')->nullable()->unsigned();
            $table->foreign('event_id')->nullable()->references('id')->on('log_events');
        });

        Schema::table('currencies_log', function (Blueprint $table) {
            $table->integer('event_id')->nullable()->unsigned();
            $table->foreign('event_id')->nullable()->references('id')->on('log_events');
        });

        Schema::table('items_log', function (Blueprint $table) {
            $table->integer('event_id')->nullable()->unsigned();
            $table->foreign('event_id')->nullable()->references('id')->on('log_events');
        });

        Schema::table('shop_log', function (Blueprint $table) {
            $table->integer('event_id')->nullable()->unsigned();
            $table->foreign('event_id')->nullable()->references('id')->on('log_events');
        });

        Schema::table('user_character_log', function (Blueprint $table) {
            $table->integer('event_id')->nullable()->unsigned();
            $table->foreign('event_id')->nullable()->references('id')->on('log_events');
        });

        Schema::table('user_update_log', function (Blueprint $table) {
            $table->integer('event_id')->nullable()->unsigned();
            $table->foreign('event_id')->nullable()->references('id')->on('log_events');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('logs');

        Schema::table('admin_log', function (Blueprint $table) {
            $table->dropcolumn('event_id');
        });

        Schema::table('awards_log', function (Blueprint $table) {
            $table->dropcolumn('event_id');
        });

        Schema::table('character_log', function (Blueprint $table) {
            $table->dropcolumn('event_id');
        });

        Schema::table('currencies_log', function (Blueprint $table) {
            $table->dropcolumn('event_id');
        });

        Schema::table('items_log', function (Blueprint $table) {
            $table->dropcolumn('event_id');
        });

        Schema::table('shop_log', function (Blueprint $table) {
            $table->dropcolumn('event_id');
        });

        Schema::table('user_character_log', function (Blueprint $table) {
            $table->dropcolumn('event_id');
        });

        Schema::table('user_update_log', function (Blueprint $table) {
            $table->dropcolumn('event_id');
        });
    }
}
