<?php

namespace App\Models;

// use Illuminate\Auth\Authenticatable;
// use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
// use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
// use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
// use Laravel\Lumen\Auth\Authorizable;

// class User extends Model implements AuthenticatableContract, AuthorizableContract
class User extends Model
{
    // use Authenticatable, Authorizable, HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'email', 'password', 'nama', 'hp_kode', 'hp_nomor', 'status', 'parent_id', 'token_push'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var string[]
     */
    protected $hidden = [
        'password',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'status' => 'Y',
    ];
}
