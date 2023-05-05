<?php

namespace Transmorpher\Models;

use Transmorpher\Enums\State;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransmorpherUpload extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'state',
        'message',
        'upload_token',
        'transmorpher_media_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'state' => State::class,
    ];

    /**
     * Returns the TransmorpherMedia this upload entry belongs to.
     *
     * @return BelongsTo
     */
    public function TransmorpherMedia(): BelongsTo
    {
        return $this->belongsTo(TransmorpherMedia::class);
    }
}