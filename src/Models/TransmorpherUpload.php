<?php

namespace Transmorpher\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Transmorpher\Enums\State;

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

    public function getRouteKeyName()
    {
        return 'token';
    }

    public function complete(array $response, int $httpCode = null): array
    {
        $transmorpher = $this->TransmorpherMedia->getTransmorpher();

        // This step can be skipped if the client response was already determined.
        if ($httpCode) {
            $response = $transmorpher->getClientResponse($response, $httpCode);
        }

        if ($response['success']) {
            $transmorpher->updateModelsAfterSuccessfulUpload($response, $this);
        } else {
            $this->update(['state' => State::ERROR, 'message' => $response['serverResponse']]);
        }

        return $response;
    }
}
