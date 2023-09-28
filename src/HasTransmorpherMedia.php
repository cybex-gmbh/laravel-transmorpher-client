<?php

namespace Transmorpher;

use Illuminate\Support\Collection;
use ReflectionClass;
use ReflectionMethod;
use Transmorpher\Exceptions\MissingMorphAliasException;
use Transmorpher\Models\TransmorpherMedia;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasTransmorpherMedia
{
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
        return collect($this->transmorpherImages)->mapWithKeys(function (string $mediaName) {
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
        return collect($this->transmorpherVideos)->mapWithKeys(function (string $mediaName) {
            return [$mediaName => Video::getInstanceFor($this, $mediaName)];
        });
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
        return $this->getMediaMethods(Image::class)->merge($this->transmorpherImages ?? [])->unique()->sort(SORT_NATURAL);
    }

    /**
     * @return Collection
     */
    public function getTransmorpherVideos(): Collection
    {
        return $this->getMediaMethods(Video::class)->merge($this->transmorpherVideos ?? [])->unique()->sort(SORT_NATURAL);
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
            if (is_a($reflectionMethod->getReturnType()?->getName(), $mediaClass, true)) {
                $mediaMethods->push($reflectionMethod->getName());
            }
        }

        return $mediaMethods;
    }
}
