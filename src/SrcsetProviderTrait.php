<?php

namespace Kinglozzer\SilverstripePicture;

use SilverStripe\Assets\Image;

trait SrcsetProviderTrait
{
    /**
     * The source Silverstripe image for the picture
     */
    protected Image $sourceImage;

    /**
     * A list of image candidates for the srcset attribute
     */
    protected array $imageCandidates = [];

    public function __construct(Image $sourceImage, array $candidatesConfig)
    {
        $this->sourceImage = $sourceImage;
        $this->prepareImageCandidates($candidatesConfig);
    }

    /**
     * Allows method calls on the underlying image from templates
     */
    public function hasMethod($method): bool
    {
        return $this->sourceImage->hasMethod($method);
    }

    /**
     * When a method is called, we assume it's a manipulation call from a template, so perform the requested
     * manipulation on *all* images. Anticipated use-case example: a Format('webp') call
     */
    public function __call($method, $arguments)
    {
        $clone = clone $this;
        $clone->manipulateSrcsetImageCandidates($method, $arguments);
        return $clone;
    }

    public function getSourceImage(): Image
    {
        return $this->sourceImage;
    }

    public function setSourceImage(Image $image): self
    {
        $this->sourceImage = $image;
        return $this;
    }

    public function getImageCandidates(): array
    {
        return $this->imageCandidates;
    }

    public function setImageCandidates(array $candidates): self
    {
        $this->imageCandidates = $candidates;
        return $this;
    }

    /**
     * Prepares a list of candidate images for the srcset attribute
     */
    protected function prepareImageCandidates(array $candidatesConfig)
    {
        foreach ($candidatesConfig as $config) {
            $manipulations = $config['manipulations'] ?? [$config];
            $descriptor = $config['descriptor'] ?? '';
            $image = $this->sourceImage;
            foreach ($manipulations as $manipulation) {
                $method = $manipulation['method'];
                $arguments = $manipulation['arguments'] ?? [];
                $image = $image->$method(...$arguments);
            }

            $this->imageCandidates[] = [
                'manipulations' => $manipulations,
                'image' => $image,
                'descriptor' => $descriptor
            ];
        }
    }

    /**
     * Performs the requested manipulation on all image candidates
     */
    protected function manipulateSrcsetImageCandidates(string $method, array $arguments = [])
    {
        foreach ($this->imageCandidates as &$imageCandidate) {
            /** @var Image $image */
            $image = $imageCandidate['image'];
            if (!$image) {
                continue;
            }

            $imageCandidate['image'] = $image->$method(...$arguments);
            $imageCandidate['manipulations'][] = [
                'method' => $method,
                'arguments' => $arguments
            ];
        }
    }

    /**
     * Returns a srcset attribute value - a comma-separated list of image candidates & descriptors
     */
    public function getImageCandidatesString(): string
    {
        $srcsetCandidates = [];
        foreach ($this->imageCandidates as $imageCandidate) {
            /** @var Image $image */
            $image = $imageCandidate['image'];
            if (!$image) {
                continue;
            }

            $srcsetCandidate = $image->getUrl();
            if ($imageCandidate['descriptor']) {
                $srcsetCandidate = "{$srcsetCandidate} {$imageCandidate['descriptor']}";
            }
            $srcsetCandidates[] = $srcsetCandidate;
        }

        return implode(', ', $srcsetCandidates);
    }
}
