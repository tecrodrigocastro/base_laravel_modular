<?php

namespace Acme\Identity\Models;

use Acme\Identity\Database\Factories\AdminFactory;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * Staff/admin identity — separate table from `users` on purpose (a customer
 * and a staff member are different concepts, see RN-style reasoning in this
 * module's own docs). Shared via packages/* the same way User is, in case a
 * future feature needs "who did this" across apps, not because two apps
 * currently both authenticate admins.
 *
 * @property int $id
 * @property bool $status
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property string|null $avatar_url
 * @property array<array-key, mixed>|null $custom_fields
 * @property string|null $locale
 * @property string|null $theme_color
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Admin extends Model implements AuthenticatableContract, AuthorizableContract, CanResetPasswordContract, MustVerifyEmailContract
{
    use Authenticatable;
    use Authorizable;
    use CanResetPassword;

    /** @use HasFactory<AdminFactory> */
    use HasFactory;

    use MustVerifyEmail;
    use Notifiable;

    protected $table = 'admins';

    protected $fillable = [
        'status',
        'name',
        'email',
        'password',
        'avatar_url',
        'custom_fields',
        'locale',
        'theme_color',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected static function newFactory(): AdminFactory
    {
        return AdminFactory::new();
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => 'boolean',
            'custom_fields' => 'array',
        ];
    }
}
