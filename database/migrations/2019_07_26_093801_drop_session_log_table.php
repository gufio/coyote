<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DropSessionLogTable extends Migration
{
    use SchemaBuilder;

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->schema->drop('session_log');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->schema->create('session_log', function (Blueprint $table) {
            $table->string('id')->nullable();
            $table->string('ip', 45);
            $table->mediumInteger('user_id')->nullable();
            $table->dateTimeTz('created_at')->default(DB::raw('CURRENT_TIMESTAMP(0)'));
            $table->dateTimeTz('updated_at')->default(DB::raw('CURRENT_TIMESTAMP(0)'));
            $table->string('url', 4000)->nullable();
            $table->string('browser', 1000)->nullable();
            $table->string('robot')->nullable();

            $table->unique('user_id');
            $table->unique('id');
        });
    }
}
