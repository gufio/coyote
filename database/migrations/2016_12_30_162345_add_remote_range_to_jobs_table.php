<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRemoteRangeToJobsTable extends Migration
{
    use SchemaBuilder;

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->schema->table('jobs', function (Blueprint $table) {
            $table->addColumn('tinyInteger', 'remote_range')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->schema->table('jobs', function (Blueprint $table) {
            $table->dropColumn('remote_range');
        });
    }
}
