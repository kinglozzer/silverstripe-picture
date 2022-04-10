<?php

namespace Kinglozzer\SilverstripePicture;

use SilverStripe\Core\Extension;

class ImageExtension extends Extension
{
    /**
     * Defines extra methods for each style registered in config
     */
    public function allMethodNames(): array
    {
        return array_map('strtolower', array_keys(Picture::config()->get('styles')));
    }

    /**
     * When a style's method is called, return a picture
     */
    public function __call(string $method, array $args = [])
    {
        return Picture::create($this->owner, strtolower($method));
    }
}
