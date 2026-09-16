<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Identificador que se almacena dentro del JWT.
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Claims adicionales del JWT.
     */
    public function getJWTCustomClaims(): array
    {
        return [];
    }

    /**
     * Cuenta bancaria del usuario.
     */
    public function account()
    {
        return $this->hasOne(Account::class);
    }
    public function savedAccounts()
    {
        return $this->belongsToMany(Account::class, 'saved_accounts');
    }
}