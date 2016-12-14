<?php

namespace Dixie\EloquentModelFuture\Tests;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;
use PHPUnit_Framework_TestCase;
use Dixie\EloquentModelFuture\Contracts\ModelFuture;
use Carbon\Carbon;
use Mockery;
use Illuminate\Support\Facades\Config;

abstract class TestCase extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        Eloquent::unguard();
        $db = new DB;
        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
        $db->bootEloquent();
        $db->setAsGlobal();

        $this->schema()->create('users', function ($table) {
            $table->increments('id');
            $table->string('email');
            $table->string('name');
            $table->text('bio')->nullable();
            $table->timestamp('birthday')->nullable();
            $table->timestamps();
        });

        $this->schema()->create('futures', function ($table) {
            $table->increments('id');
            $table->integer('createe_user_id')->unsinged()->nullable();
            $table->morphs('futureable');
            $table->json('data');
            $table->timestamp('commit_at');
            $table->timestamp('committed_at')->nullable()->default(null);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['committed_at', 'commit_at'], 'committed_at_and_commit_at');
        });
    }

    public function tearDown()
    {
        Mockery::close();
        $this->schema()->drop('users');
        $this->schema()->drop('futures');
    }

    protected function schema()
    {
        return $this->connection()->getSchemaBuilder();
    }

    protected function connection()
    {
        return Eloquent::getConnectionResolver()->connection();
    }

    protected function createFuturePlanFor(ModelFuture $model, $date, array $data = [], $shouldOverride = false)
    {
        $attributes = array_merge($data, [
            'name' => 'John Doe',
            'email' => 'jo.do@dixie.io',
        ]);

        if($shouldOverride) {
            $attributes = $data;
        }

        return $model->future()->plan($attributes)->at($date);
    }

    protected function createUser(array $data = [], $shouldOverride = false)
    {
        $attributes = array_merge($data, [
            'name' => 'Jakob Steinn',
            'email' => 'ja.st@dixie.io',
            'bio' => 'I am a developer at dixie.io',
            'birthday' => Carbon::now()->subYear(),
        ]);

        if($shouldOverride) {
            $attributes = $data;
        }

        return User::create($attributes);
    }
}

class User extends Eloquent implements ModelFuture
{
    use \Dixie\EloquentModelFuture\Traits\HasFuture;
}

