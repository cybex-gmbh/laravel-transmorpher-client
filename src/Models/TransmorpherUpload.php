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
        'token',
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
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::saved(function (TransmorpherUpload $transmorpherUpload) {
            // Only update the corresponding TransmorpherMedia model if this is the latest upload.
            if ($transmorpherUpload->is($transmorpherUpload->TransmorpherMedia->TransmorpherUploads()->latest()->first())) {
                $transmorpherUpload->TransmorpherMedia()->update(['latest_upload_state' => $transmorpherUpload->state, 'latest_upload_token' => $transmorpherUpload->token]);
            }
        });
    }

    /**
     * Returns the TransmorpherMedia this upload belongs to.
     *
     * @return BelongsTo
     */
    public function TransmorpherMedia(): BelongsTo
    {
        return $this->belongsTo(TransmorpherMedia::class);
    }
}
