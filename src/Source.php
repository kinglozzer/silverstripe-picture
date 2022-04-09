<?php

namespace Kinglozzer\SilverstripePicture;

use BadMethodCallException;
use SilverStripe\Assets\Image;
use SilverStripe\View\HTML;
use SilverStripe\View\ViewableData;

class Source extends ViewableData
{
    protected Image $sourceImage;

    protected string $media;

    protected array $config;

    protected array $imageCandidates = [];

    public function __construct(Image $sourceImage, string $media, array $config)
    {
        $this->sourceImage = $sourceImage;
        $this->media = $media;
        $this->config = $config;
        $this->prepareImageCandidates();
    }

    public function hasMethod($method)
    {
        return parent::hasMethod($method) || $this->sourceImage->hasMethod($method);
    }

    public function __call($method, $arguments)
    {
        foreach ($this->imageCandidates as &$imageCandidate) {
            /** @var Image $image */
            $image = $imageCandidate['image'];
            $imageCandidate['image'] = $image->$method(...$arguments);
            $imageCandidate['manipulations'][] = [
                'method' => $method,
                'arguments' => $arguments
            ];
        }

        return $this;
    }

    protected function prepareImageCandidates()
    {
        foreach ($this->config as $config) {
            $manipulations = $config['manipulations'] ?? [$config];
            $descriptor = $config['descriptor'] ?? '';
            $image = $this->sourceImage;
            foreach ($manipulations as $manipulation) {
                $method = $manipulation['method'];
                $arguments = $manipulation['arguments'];
                $image = $image->$method(...$arguments);
            }

            $this->imageCandidates[] = [
                'manipulations' => $manipulations,
                'image' => $image,
                'descriptor' => $descriptor
            ];
        }
    }

    public function forTemplate()
    {
        $attributes = [
            'media' => $this->media
        ];

        $lastImage = null;
        $srcsetCandidates = [];
        foreach ($this->imageCandidates as $imageCandidate) {
            /** @var Image $image */
            $image = $imageCandidate['image'];
            $lastImage = $image;

            $srcsetCandidate = $image->getUrl();
            if ($imageCandidate['descriptor']) {
                $srcsetCandidate = "{$srcsetCandidate} {$imageCandidate['descriptor']}";
            }
            $srcsetCandidates[] = $srcsetCandidate;
        }

        $attributes['srcset'] = implode(', ', $srcsetCandidates);
        $attributes['type'] = $lastImage ? $lastImage->getMimeType() : $this->sourceImage->getMimeType();

        $this->extend('updateAttributes', $attributes);
        return HTML::createTag('source', $attributes);
    }
}
