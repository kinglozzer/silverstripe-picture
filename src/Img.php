<?php

namespace Kinglozzer\SilverstripePicture;

use SilverStripe\Assets\Image;
use SilverStripe\View\ViewableData;

class Img extends ViewableData
{
    use SrcsetProviderTrait {
        __construct as __srcsetProviderConstruct;
        __call as __srcsetProviderCall;
    }

    /**
     * The default image for this <picture> element, to be rendered as a nested <img /> tag
     */
    protected Image $defaultImage;

    /**
     * A store of any manipulations performed on the default image
     */
    protected array $defaultImageManipulations = [];

    public function __construct(Image $sourceImage, array $config)
    {
        parent::__construct();
        $this->__srcsetProviderConstruct($sourceImage, $config);
        $this->defaultImage = $sourceImage;
    }

    /**
     * For the default Img tag, we ensure that any requested manipulation is also called against the
     * default image that will become the "src", not just the srcset attribute image candidates
     */
    public function __call($method, $arguments)
    {
        $this->defaultImage = $this->defaultImage->$method(...$arguments);
        $this->defaultImageManipulations[] = [
            'method' => $method,
            'arguments' => $arguments
        ];

        return $this->__srcsetProviderCall($method, $arguments);
    }

    /**
     * For the default <img> tag, we re-use the existing Silverstripe Image object and inject a srcset attribute
     */
    public function forTemplate(): string
    {
        $attributes = [
            'srcset' => $this->getImageCandidatesString()
        ];

        $this->extend('updateAttributes', $attributes);

        $defaultImage = $this->imageCandidates[0]['image'] ?? $this->defaultImage;
        foreach ($attributes as $attribute => $value) {
            $defaultImage = $defaultImage->setAttribute($attribute, $value);
        }

        return $defaultImage->forTemplate();
    }
}
