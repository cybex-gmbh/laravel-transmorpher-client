<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements \Transmorpher\HasTransmorpherMediaInterface
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;
    use \Transmorpher\HasTransmorpherMedia;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected array $transmorpherImages = [
        'front',
        'back'
    ];

    protected array $transmorpherDocuments = [
        'document',
        'user-guide'
    ];

    protected array $transmorpherVideos = [
        'teaser',
        'full'
    ];
}
