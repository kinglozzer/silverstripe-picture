<?php

namespace Kinglozzer\SilverstripePicture;

use SilverStripe\Assets\Image;
use SilverStripe\View\HTML;
use SilverStripe\View\ViewableData;

class Img extends ViewableData
{
    protected Image $sourceImage;

    protected Image $defaultImage;

    protected array $config;

    protected array $imageCandidates = [];

    protected array $defaultImageManipulations = [];

    public function __construct(Image $sourceImage, array $config)
    {
        $this->sourceImage = $sourceImage;
        $this->defaultImage = $this->sourceImage;
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

        $this->defaultImage = $this->defaultImage->$method(...$arguments);
        $this->defaultImageManipulations[] = [
            'method' => $method,
            'arguments' => $arguments
        ];

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
        $attributes = [];

        $firstImage = null;
        $srcsetCandidates = [];
        foreach ($this->imageCandidates as $imageCandidate) {
            /** @var Image $image */
            $image = $imageCandidate['image'];
            if (!$firstImage) {
                $firstImage = $image;
            }

            $srcsetCandidate = $image->getUrl();
            if ($imageCandidate['descriptor']) {
                $srcsetCandidate = "{$srcsetCandidate} {$imageCandidate['descriptor']}";
            }
            $srcsetCandidates[] = $srcsetCandidate;
        }

        $attributes['srcset'] = implode(', ', $srcsetCandidates);

        $this->extend('updateAttributes', $attributes);

        $defaultImage = $firstImage ?? $this->defaultImage;
        foreach ($attributes as $attribute => $value) {
            $defaultImage = $defaultImage->setAttribute($attribute, $value);
        }

        return $defaultImage->forTemplate();
    }
}
