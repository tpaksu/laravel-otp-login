<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;

class CreateOtpTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'one_time_passwords', function (Blueprint $table) {
                $table->bigIncrements('id');
                if (version_compare(app()->version(), '5.8.0', '<')) {
                    $table->unsignedInteger("user_id")->index();
                } else {
                    $table->unsignedBigInteger("user_id")->index();
                }
                $table->string('status')->index();
                $table->timestamps();
            }
        );

        Schema::table(
            'one_time_passwords', function (Blueprint $table) {
                $usersTable = config('otp.users_table', 'users');
                $userIdField = config('otp.user_id_field', 'id');
                $table->foreign('user_id') ->references($userIdField)->on($usersTable)->onDelete('cascade');
            }
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('one_time_passwords');
    }
}
