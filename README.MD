# WP Responsive Images

<!--suppress HtmlDeprecatedAttribute -->


<strong>A library for easy introduction of Responsive Images to WordPress site.</strong>

## Requirements

- [PHP](https://secure.php.net/manual/en/install.php) 8.0
- [WordPress](https://wordpress.org/)

## Installation

Clone WP Responsive Images library or install with composer:

```bash
$ composer require vvv3/wp-responsive-images
```

## How to use

The library contains classes and helpers for easy introduction of Responsive Images to a WordPress site.

### ImgUtils::resizeImg()

The `\WPRI\ImgUtils::resizeImg` method returns a link to the modified image according to the passed parameters.

**Usage:**

```php
 <img src="<?php echo ImgUtils::resizeImg( $url, $width, $height, $crop ); ?>" >
```

- `$url` - URL to image file to resize
- `$width` - width value, integer
- `$height` - height value, integer
- `$crop` - bool flag crop or not

**Returns:**
`string` resized image url

**Example:**

```php
use WPRI;
...
<img src="<?php echo ImgUtils::resizeImg( get_the_post_thumbnail_url( get_the_ID(), 'full' ), 380, 250, true ); ?>" >
```

### ImgUtils::imgForPost, ImgUtils::pictureForPost

Helpers `ImgUtils::imgForPost` and `ImgUtils::pictureForPost` return a Responsive &lt;img&gt; or &lt;picture&gt; tag by post Id. You may set list of media
queries with image with for this query, double pixel ratio, aspect ratio for the images, loading="lazy" native attribute or any other additional attribute.

**Usage:**

    ImgUtils::imgForPost(
        int $postId,
        array $mqWithWidth = [],
        bool $pixelRatio2x = false,
        int|float|null $aspectRatio = null,
        bool $lazy = false,
        array $attrs = [])

    ImgUtils::pictureForPost(
        int $postId,
        array $mqWithWidth = [],
        bool $pixelRatio2x = false,
        int|float|null $aspectRatio = null,
        bool $lazy = false,
        array $attrs = [])

**Returns:**

    string <img> or <picture> tag accordingly or empty string on error

**Example:**

```php
echo ImgUtils::imgForPost(
		get_the_ID(),
		[   // format: ['media-query' => width-in-px(int), ... , default-width-in-px(int) ]
			'(min-width: 1200px)' => 730,
			'(min-width: 768px)'  => 690,
			545, // dafault width, if no media query aplied
		],
		false,
		16/9,
		true,
		['class' => 'my-class']
	);

echo ImgUtils::pictureForPost(
		get_the_ID(),
		[
			'(max-width: 767px)'  => 545,
			'(max-width: 1199px)' => 690,
			730
		] );
```

### ImgUtils::imgByAttachmentId, ImgUtils::pictureByAttachmentId

Helpers ImgUtils::imgByAttachmentId and ImgUtils::pictureByAttachmentId return a Responsive &lt;img&gt; , &lt;picture&gt; tag by attachment Id.

### Also you can do any combination of parameters of Picture or Img responsive tags with WPRI\ResponsiveImages\ classes :

```php
if ( has_post_thumbnail( $postId ) ) {
    try {
        $originUrl = get_the_post_thumbnail_url( $postId, 'full' );
        $resizer   = Resizer::makeWithUrl( $originUrl );
        $picture =
            Picture::make(
                $originUrl, // original url for default img tag
                'customAlt', // alt for default img tag
                null, // width attribute, by default as at $originUrl image
                null, // height attribute, by default as at $originUrl image
                [     // sourses
                    Source::make(
                       [ // SrcsetItems
                        SrcsetItem::makeWithResize( $resizer->setWidth( 730 ), '1x' ),
                        SrcsetItem::makeWithResize( $resizer->setWidth( 730 * 2 ), '2x' ),
                    ],
                        [], // may add sizes
                        '(min-width: 1200px)', // may add media
                        'image/jpeg'  // may add image mime-type
                    ),

                    Source::make( [
                        SrcsetItem::makeWithResize( $resizer->setWidth( 690 ), '1x' ),
                        SrcsetItem::makeWithResize( $resizer->setWidth( 690 * 2 ), '2x' ),
                    ],
                        [],
                        '(min-width: 768px)'
                    ),

                    Source::make( [
                        SrcsetItem::makeWithResize( $resizer->setWidth( 545 ), '1x' ),
                        SrcsetItem::makeWithResize( $resizer->setWidth( 545 * 2 ), '2x' ),
                    ] ),
                ] )
                   ->setPictureAttr( 'class', 'custom-picture-class' )
                   ->setPictureAttr( 'data-some', 'custom-picture-attr' )
                   ->setImgAttr( 'class', 'custom-img-class' )
                   ->setImgAttr( 'data-some', 'custom-img-attr' )
                   ->render();
    } catch ( \Exception $ex ) {
        error_log( "\nFile: {$ex->getFile()}\nLine: {$ex->getLine()}\nMessage: {$ex->getMessage()}\n" );
        $picture = '';
    }
    echo $picture;
}
```
