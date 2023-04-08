<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFromToTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('from_to', function (Blueprint $table) {
            $table->id();
            $table->text('filial')->nullable();
            $table->text('command')->nullable(); 
            $table->text('field')->nullable(); 
            $table->text('value_origin')->nullable();
            $table->text('value_destiny')->nullable();
            $table->text('text')->nullable();        
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
        Schema::dropIfExists('from_to');
    }
}
