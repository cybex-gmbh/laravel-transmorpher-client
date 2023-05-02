<?php

namespace Transmorpher\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Transmorpher\Enums\MediaType;
use Transmorpher\Enums\State;
use Transmorpher\ImageTransmorpher;
use Transmorpher\Transmorpher;
use Transmorpher\VideoTransmorpher;

class TransmorpherMedia extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'transmorphable_type',
        'transmorphable_id',
        'differentiator',
        'public_path',
        'type',
        'is_ready',
        'last_response',
        'last_upload_token'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'type' => MediaType::class,
        'last_response' => State::class,
    ];


    /**
     * Return the parent transmorphable model.
     *
     * @return MorphTo
     */
    public function Transmorphable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Return TransmorpherProtocols for this TransmorpherMedia.
     *
     * @return HasMany
     */
    public function TransmorpherProtocols(): HasMany
    {
        return $this->hasMany(TransmorpherProtocol::class);
    }

    public function getTransmorpher(): Transmorpher
    {
        return $this->type === MediaType::IMAGE
            ? ImageTransmorpher::getInstanceFor($this->Transmorphable, $this->differentiator)
            : VideoTransmorpher::getInstanceFor($this->Transmorphable, $this->differentiator);
    }
}
