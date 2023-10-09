<?php

namespace Transmorpher;

use Illuminate\Support\Collection;
use ReflectionClass;
use ReflectionMethod;
use Transmorpher\Exceptions\DuplicateMediaNameException;
use Transmorpher\Exceptions\MissingMorphAliasException;
use Transmorpher\Models\TransmorpherMedia;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasTransmorpherMedia
{
    protected static Collection $cachedTransmorpherImages;
    protected static Collection $cachedTransmorpherVideos;

    /**
     * @throws MissingMorphAliasException
     */
    public static function bootHasTransmorpherMedia()
    {
        if (static::getModel()->getTransmorpherAlias() === static::class) {
            throw new MissingMorphAliasException(static::class);
        }
    }

    /**
     * Return all transmorpher media.
     *
     * @return MorphMany
     */
    public function TransmorpherMedia(): MorphMany
    {
        return $this->morphMany(TransmorpherMedia::class, 'transmorphable');
    }

    /**
     * Returns a collection of Images associated to media names.
     *
     * @return Collection
     */
    public function images(): Collection
    {
        return $this->getTransmorpherImages()->mapWithKeys(function (string $mediaName) {
            return [$mediaName => Image::getInstanceFor($this, $mediaName)];
        });
    }

    /**
     * Returns a collection of Videos associated to media names.
     *
     * @return Collection
     */
    public function videos(): Collection
    {
        return $this->getTransmorpherVideos()->mapWithKeys(function (string $mediaName) {
            return [$mediaName => Video::getInstanceFor($this, $mediaName)];
        });
    }

    /**
     * Returns a single Image for a media name.
     *
     * @param string $mediaName
     * @return Image|null
     */
    public function image(string $mediaName): ?Image
    {
        if ($this->getTransmorpherImages()->contains($mediaName)) {
            return Image::getInstanceFor($this, $mediaName);
        }

        return null;
    }

    /**
     * Returns a single Video for a media name.
     *
     * @param string $mediaName
     * @return Video|null
     */
    public function video(string $mediaName): ?Video
    {
        if ($this->getTransmorpherVideos()->contains($mediaName)) {
            return Video::getInstanceFor($this, $mediaName);
        }

        return null;
    }

    /**
     * @return string
     */
    public function getTransmorpherAlias(): string
    {
        return $this->transmorpherAlias ?? $this->getMorphClass();
    }

    /**
     * @return Collection
     */
    public function getTransmorpherImages(): Collection
    {
        return static::$cachedTransmorpherImages ??= $this->getTransmorpherMedia(Image::class, $this->transmorpherImages ?? []);
    }

    /**
     * @return Collection
     */
    public function getTransmorpherVideos(): Collection
    {
        return static::$cachedTransmorpherVideos ??= $this->getTransmorpherMedia(Video::class, $this->transmorpherVideos ?? []);
    }

    /**
     * @param string $mediaClass
     * @param array $mediaArray
     * @return Collection
     * @throws DuplicateMediaNameException
     */
    protected function getTransmorpherMedia(string $mediaClass, array $mediaArray): Collection
    {
        $mediaMethods = $this->getMediaMethods($mediaClass);
        $loweredMediaMethods = $mediaMethods->map('strtolower');
        $loweredMediaArray = array_map('strtolower', $mediaArray);

        $duplicates = array_diff_key($loweredMediaArray, array_unique($loweredMediaArray));
        $duplicates = $duplicates ? collect($duplicates) : $loweredMediaMethods->intersect($loweredMediaArray);

        if ($duplicates->count()) {
            throw new DuplicateMediaNameException($this::class, $duplicates);
        }

        return $mediaMethods->merge($mediaArray)->sort(SORT_NATURAL);
    }

    /**
     * @param string $mediaClass
     * @return Collection
     */
    protected function getMediaMethods(string $mediaClass): Collection
    {
        $mediaMethods = collect();
        $reflectionClass = new ReflectionClass($this);

        foreach ($reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC) as $reflectionMethod) {
            $reflectionMethodName = $reflectionMethod->getName();

            if (is_a($reflectionMethod->getReturnType()?->getName(), $mediaClass, true) && strtolower($reflectionMethodName) !== 'image' && strtolower($reflectionMethodName) !== 'video') {
                $mediaMethods->push($reflectionMethodName);
            }
        }

        return $mediaMethods;
    }
}
