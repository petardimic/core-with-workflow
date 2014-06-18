<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateObjectFieldTable extends Migration {

	public function up()
	{
		if (!Schema::hasTable('object_field'))
		{
			Schema::create('object_field', function(Blueprint $table)
			{
				$table->increments('id');
				$table->timestamps();
				$table->softDeletes();

				$table->text('title')->nullable();
				$table->text('title_list')->nullable();
				$table->string('code')->nullable()->default(null);
				$table->integer('created_by_user')->unsigned()->nullable()->default(null);
				$table->integer('updated_by_user')->unsigned()->nullable()->default(null);
				$table->integer('deleted_by_user')->unsigned()->nullable()->default(null);
				$table->integer('active')->unsigned()->nullable()->default(null);
				$table->timestamp('start_at');
				$table->timestamp('end_at');
				$table->string('key')->nullable()->default(null);
				$table->string('rule')->nullable()->default(null);
				$table->integer('field_object_type')->unsigned()->default(null);
				$table->integer('field_object_tab')->unsigned()->nullable()->default(null);
				$table->integer('required')->unsigned()->nullable()->default(null);
				$table->integer('show_in_list')->unsigned()->nullable()->default(0);
				$table->integer('show_in_form')->unsigned()->nullable()->default(0);
				$table->integer('allow_search')->unsigned()->nullable()->default(0);
				$table->integer('allow_delete')->unsigned()->nullable()->default(0);
				$table->integer('allow_create')->unsigned()->nullable()->default(1);
				$table->integer('allow_choose')->unsigned()->nullable()->default(1);
				$table->integer('allow_update')->unsigned()->nullable()->default(1);
				$table->integer('allow_sort')->unsigned()->nullable()->default(0);
				$table->integer('multilanguage')->unsigned()->nullable()->default(0);
				$table->integer('field_order')->unsigned()->nullable()->default(0);
				$table->string('css_class')->nullable()->default(null);
				$table->string('icon_class')->nullable()->default(null);
				$table->text('description')->nullable();

				$table->index('field_object_type');
			});
		}
	}

}