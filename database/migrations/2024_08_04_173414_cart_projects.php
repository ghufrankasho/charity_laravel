<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CartProjects extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cart_projects', function (Blueprint $table) {
            $table->id(); 
            $table->foreignId('project_id')->constrained('projects','id');
            $table->foreignId('cart_id')->constrained('carts','id');
            // $table->primary(['project_id','caert_id']);
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
        Schema::dropIfExists('cart_projects');
    }
}