<?php

namespace tpaksu\LaravelOTPLogin\Tests\Helpers;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;

class User extends Model implements AuthorizableContract, AuthenticatableContract
{
    use Authorizable;
    use Authenticatable;

    protected $guarded = [];
    public $timestamps = false;
}
