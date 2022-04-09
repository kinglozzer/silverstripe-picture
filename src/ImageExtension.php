<?php

namespace Kinglozzer\SilverstripePicture;

use Kinglozzer\SilverstripePicture\Picture;
use SilverStripe\Core\Extension;

class ImageExtension extends Extension
{
    public function allMethodNames(): array
    {
        return array_map('strtolower', array_keys(Picture::config()->get('styles')));
    }

    public function __call($method, $args)
    {
        return Picture::create($this->owner, strtolower($method));
    }
}
