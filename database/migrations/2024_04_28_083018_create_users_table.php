<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('image')->default(null);
            $table->string('cv')->default(null);
            $table->string('address');
            $table->boolean('accept')->default(false);
            $table->string('mobile');
            $table->integer('salary');
            $table->string('guardian')->default(null);
            $table->foreignId('work_id')->constrained('works','id');
            $table->foreignId('project_id')->constrained('projects','id');
            $table->foreignId('account_id')->constrained('accounts','id');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
};