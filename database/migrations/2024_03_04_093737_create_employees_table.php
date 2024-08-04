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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->references('id')->on('departments')->onDelete('cascade')->default(null);
            $table->foreignId('account_id')->references('id')->on('accounts')->onDelete('cascade')->default(null);
            $table->string('name');
            $table->string('image')->default(null);
            $table->string('phone');
            $table->integer('salary');
            $table->boolean('employed')->default(false);
            $table->boolean('avaliable')->default(true);
            $table->string('address');
            $table->string('email')->unique();
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
        Schema::dropIfExists('employees');
    }
};