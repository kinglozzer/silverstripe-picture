# Silverstripe Picture

Easy `<picture>` element generation for Silverstripe images.

## Installation

```bash
composer require kinglozzer/silverstripe-picture
```

## Usage

Configuration is performed via YAML:

```yml
Kinglozzer\SilverstripePicture\Picture:
  styles:
    Carousel:
      default:
        - {method: 'Fill', arguments: [375, 250]}
        - {method: 'Fill', arguments: [750, 500], descriptor: 750w}
      sources:
        "(min-width: 1024px)":
          - {method: 'Fill', arguments: [1024, 450]}
          - {method: 'Fill', arguments: [2048, 900], descriptor: 2x}
```

Each style can then be called from templates:

```html
<div class="carousel__slide">
    {$Image.Carousel}
</div>
```

The generated HTML will look like this:

```html
<picture>
    <source media="(min-width: 1024px)" srcset="/assets/image__FillWzEwMjQsNDUwXQ.jpg, /assets/image__FillWzIwNDgsOTAwXQ.jpg 2x" type="image/jpeg" />
    <img width="375" height="250" alt="image" src="/assets/image__FillWzM3NSwyNTBd.jpg" loading="lazy" srcset="/assets/image__FillWzM3NSwyNTBd.jpg, /assets/image__FillWzc1MCw1MDBd.jpg 750w" />
</picture>
```

## Full example

Each source (and the “default” image) consists of one or more srcset image candidates, and each candidate consists
of one or more manipulations and an optional descriptor (e.g. `2x`, `300w`).

```yml
Kinglozzer\SilverstripePicture\Picture:
  styles:
    Carousel:
      # The default <img /> that’s rendered if none of the <source> medias are matched
      default:
        # A list of image candidates to be output in the srcset attribute on the <img /> tag
        - {method: 'Fill', arguments: [200, 150]} # The first candidate will also be used as the <img /> "src"
        - {method: 'Fill', arguments: [400, 300], descriptor: 400w}
      # A list representing the <source> tags
      sources:
        "(min-width: 1128px)": # The "media" attribute for the source
          # A list of srcset image candidates
          - {method: 'Fill', arguments: [1024, 450]}
          - {method: 'Fill', arguments: [2048, 900], descriptor: 2x}
        "(min-width: 768px)":
          # If required, each srcset candidate can perform multiple manipulations
          -
            manipulations:
              - {method: 'ScaleWidth', arguments: [50]}
              - {method: 'Pad', arguments: [50, 100, 'FFFFFF']}
          -
            manipulations:
              - {method: 'ScaleWidth', arguments: [100]}
              - {method: 'Pad', arguments: [100, 200, 'FFFFFF']}
            descriptor: 2x
```
