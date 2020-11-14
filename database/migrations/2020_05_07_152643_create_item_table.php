<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateItemTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id('id');
            $table->string('itemName');
            $table->double('itemPrice', 100, 2);
            $table->string('itemImg');
            $table->string('itemImgDir');
            $table->integer('discount')->nullable($value=true);
            $table->decimal('discountItemPrice', 4, 2)->nullable($value= true);
            $table->boolean('isOnSale')->nullable($value=true);
            $table->mediumText('itemDescription');
            $table->boolean('isEditable')->nullable($value=true);
            $table->integer('availableQuantity');
            $table->boolean('isAvailable')->nullable($value=true);
            $table->string('categories');
            $table->string('subCategories')->nullable($value=true);
            $table->string('colors');
            $table->boolean('delisted')->default(0);
            $table->string('dimensions');
            $table->integer('OverallRating')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('item');
    }
}
