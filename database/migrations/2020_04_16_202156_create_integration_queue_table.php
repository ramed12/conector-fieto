<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIntegrationQueueTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('integration_queue', function (Blueprint $table) {
            $table->id();
            $table->string('origin_model')->nullable();
            $table->string('origin_key')->nullable();
            $table->string('origin')->nullable();
            $table->string('origin_command')->nullable();  
            $table->string('destiny_model')->nullable(); 
            $table->string('destiny_key')->nullable();          
            $table->string('destiny')->nullable();
            $table->string('destiny_command')->nullable();
            $table->text('properties')->nullable();
            $table->text('error_log')->nullable();            
            $table->integer('status');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('integration_queue');
    }
}
