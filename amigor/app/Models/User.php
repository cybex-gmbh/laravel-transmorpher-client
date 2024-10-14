<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Transmorpher\HasTransmorpherMedia;
use Transmorpher\HasTransmorpherMediaInterface;
use Transmorpher\Image;
use Transmorpher\Video;

class User extends Authenticatable implements HasTransmorpherMediaInterface
{
    use HasFactory, HasTransmorpherMedia, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

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
    ];

    protected array $transmorpherVideos = [
        'teaser',
    ];

    /**
     * Example of a media method.
     *
     * @return Image
     */
    public function mediaMethod(): Image
    {
        return Image::for($this, 'back');
    }

    public function mediaMethodWithUnionType(): Image|Video
    {
        return Video::for($this, 'full');
    }
}
