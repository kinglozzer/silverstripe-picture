<?php

namespace Kinglozzer\SilverstripePicture;

use Exception;
use SilverStripe\Assets\Image;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\View\ArrayData;
use SilverStripe\View\ViewableData;

class Picture extends ViewableData
{
    private static array $styles = [];

    private static string $method = '';

    private static array $densities = [];

    protected Image $image;

    protected string $style;

    protected ?array $styleConfig;

    public function __construct(Image $image, string $style)
    {
        $this->image = $image;
        $this->style = $style;
        $styles = array_change_key_case($this->config()->get('styles'), CASE_LOWER);
        $this->styleConfig = $styles[$this->style] ?? null;
        if (!$this->styleConfig) {
            throw new Exception("Style “{$this->style}” not found");
        }
    }

    public function forTemplate(): DBHTMLText
    {
        return $this->renderWith(__CLASS__);
    }

    public function getSourceImage(): Image
    {
        return $this->image;
    }

    public function getDefaultImage(): Img
    {
        $defaultConfig = $this->styleConfig['default'] ?? null;
        if (!$defaultConfig) {
            throw new Exception("No default config set for style “{$this->style}”");
        }

        return Img::create($this->image, $defaultConfig);
    }

    public function getDefault(): ArrayData
    {
        $defaultConfig = $this->styleConfig['default'] ?? null;
        if (!$defaultConfig) {
            throw new Exception("No default config set for style “{$this->style}”");
        }

        $srcsetUrls = $this->getSrcsets($defaultConfig);
        $srcsets = [];
        foreach ($srcsetUrls as $srcsetUrl) {
            /** @var Image $image */
            $image = $srcsetUrl['image'];
            $srcset = $image->getURL();
            if ($srcsetUrl['descriptor']) {
                $srcset = "{$srcset} {$srcsetUrl['descriptor']}";
            }
            $srcsets[] = $srcset;
        }

        return ArrayData::create([
            'Image' => $srcsetUrls[0]['image'],
            'Srcset' => implode(', ', $srcsets)
        ]);
    }

    public function getSources(): ArrayList
    {
        $sourcesConfig = $this->styleConfig['sources'] ?? [];
        if (empty($sourcesConfig)) {
            throw new Exception("No sources config set for style “{$this->style}”");
        }

        $sources = ArrayList::create();
        foreach ($sourcesConfig as $media => $sourceConfig) {
            $source = Source::create($this->image, $media, $sourceConfig);
            $sources->push($source);
        }

        return $sources;
    }

    protected function getSrcsets($config): array
    {
        $srcsets = [];
        foreach ($config as $srcsetConfig) {
            $manipulations = $srcsetConfig['manipulations'] ?? [$srcsetConfig];
            $descriptor = $srcsetConfig['descriptor'] ?? '';
            $image = $this->image;
            foreach ($manipulations as $manipulation) {
                $method = $manipulation['method'];
                $arguments = $manipulation['arguments'];
                $image = $image->$method(...$arguments);
            }

            $srcsets[] = [
                'image' => $image,
                'descriptor' => $descriptor
            ];
        }

        return $srcsets;
    }
}
