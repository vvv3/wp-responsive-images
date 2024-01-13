<?php

namespace WPRI;

use WPRI\ResponsiveImages\Img;
use WPRI\ResponsiveImages\Picture;
use WPRI\ResponsiveImages\Resizer;
use WPRI\ResponsiveImages\Size;
use WPRI\ResponsiveImages\Source;
use WPRI\ResponsiveImages\SrcsetItem;

/**
 * Images Utils
 */
class ImgUtils
{

    /**
     * Resize image.
     *
     * @param string   $url Image url.
     * @param int      $width Image width.
     * @param int|null $height Optional. Image height.
     * @param bool     $crop Optional. Image cropping behavior.
     *                       If false, the image will be scaled.
     *                       If true, image will be cropped to the specified dimensions using center positions(default).
     * @param bool     $upscale Optional. Resizes smaller images.
     *
     * @return string Image path.
     */
    public static function resizeImg(
        string $url,
        int $width,
        ?int $height = null,
        bool $crop = true,
        bool $upscale = true
    ): string {
        if ( ! class_exists('Aq_Resize')) {
            require_once __DIR__ . '/../libs/aq_resizer/aq_resizer.php';
        }

        if ($url && strpos($url, 'http') !== 0) {
            $url = (\is_ssl() ? 'https:' : 'http:') . $url;
        }

        $newImgUrl = \aq_resize($url, $width, $height, $crop, true, $upscale);

        return $newImgUrl ?: $url;
    }


    /**
     * Retrieve attachment local path by it`s url.
     *
     * @param string $url Attachment url.
     *
     * @return string Attachment path.
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public static function getAttachmentPathByUrl(string $url): string
    {
        if ( ! $url) {
            throw new \InvalidArgumentException('Empty $url');
        }
        // Define upload path & dir.
        $uploadInfo = wp_upload_dir();
        $uploadDir = $uploadInfo['basedir'];
        $uploadUrl = $uploadInfo['baseurl'];

        $httpScheme = 'http://';
        $httpsScheme = 'https://';
        $relativeScheme = '//'; // The protocol-relative URL

        /* if the $url scheme differs from $uploadUrl scheme, make them match
           if the schemes differe, images don't show up. */
        if ( ! strncmp(
            $url,
            $httpsScheme,
            strlen($httpsScheme)
        )) { //if url begins with https:// make $uploadUrl begin with https:// as well
            $uploadUrl = str_replace($httpScheme, $httpsScheme, $uploadUrl);
        } elseif ( ! strncmp(
            $url,
            $httpScheme,
            strlen($httpScheme)
        )) { //if url begins with http:// make $uploadUrl begin with http:// as well
            $uploadUrl = str_replace($httpsScheme, $httpScheme, $uploadUrl);
        } elseif ( ! strncmp(
            $url,
            $relativeScheme,
            strlen($relativeScheme)
        )) { //if url begins with // make $uploadUrl begin with // as well
            $uploadUrl = str_replace(
                [$httpScheme, $httpsScheme],
                $relativeScheme,
                $uploadUrl
            );
        }

        // Check if $url is local.
        if (false === strpos($url, $uploadUrl)) {
            throw new \RuntimeException('$url is not local');
        }

        // Define path of image.
        $relPath = str_replace($uploadUrl, '', $url);
        $imgPath = $uploadDir . $relPath;

        return $imgPath;
    }


    /**
     * Gets attachment info by image path.
     *
     * @param string $imgPath Image path.
     *
     * @return array Array of results: ['width' => (int) , 'height' => (int)].
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public static function getAttachmentInfoByPath(string $imgPath): array
    {
        // Check if img path exists, and is an image indeed.
        if ( ! file_exists($imgPath)) {
            throw new \InvalidArgumentException('File not exists: $imgPath');
        }

        $imageData = \getimagesize($imgPath);

        if ( ! is_array($imageData)) {
            throw new \RuntimeException('Can`t get image size');
        }

        if (empty($imageData[0]) || empty($imageData[1])) {
            $size = '[width:' . $imageData[0] . ', height:' . $imageData[1] . ']';
            throw new \RuntimeException("Unexpected image size: $size");
        }

        return ['width' => (int)$imageData[0], 'height' => (int)$imageData[1]];
    }


    /**
     * Responsive images helper.
     * Create html tag Picture from post thumbnail.
     *
     * @param int            $postId Post Id.
     * @param array          $mqWithWidth Media queries with width,
     *                           format: ['media-query' => width-in-px(int), ... , default-width-in-px(int) ]
     * @param bool           $pixelRatio2x Whether to add images with 2x pixel ratio.
     * @param int|float|null $aspectRatio Image aspect ratio(width/height).
     * @param bool           $lazy Lazy loading.
     * @param array          $attrs Additional attributes, f.e. ['class'=>'add-class1']
     *
     * @return string Picture html tag.
     */
    public static function pictureForPost(
        int $postId,
        array $mqWithWidth = [],
        bool $pixelRatio2x = false,
        int|float|null $aspectRatio = null,
        bool $lazy = false,
        array $attrs = []
    ): string {
        if ( ! $postId || ! has_post_thumbnail($postId)) {
            return '';
        }

        $originUrl = get_the_post_thumbnail_url($postId, 'full');
        $imgAlt = esc_attr(strip_tags(get_the_title($postId)));

        return static::picture($originUrl, $mqWithWidth, $pixelRatio2x, $aspectRatio, $imgAlt, $lazy, $attrs);
    }


    /**
     * Responsive images helper.
     * Create html tag Picture by attachment Id.
     *
     * @param int            $attachmentId Attachment Id.
     * @param array          $mqWithWidth Media queries with width,
     *                           format: ['media-query' => width-in-px(int), ... , default-width-in-px(int) ]
     * @param bool           $pixelRatio2x Whether to add images with 2x pixel ratio.
     * @param int|float|null $aspectRatio Image aspect ratio(width/height).
     * @param string         $imgAlt Image alt attribute string.
     * @param bool           $lazy Lazy loading.
     * @param array          $attrs Additional attributes, f.e. ['class'=>'add-class1']
     *
     * @return string Picture html tag.
     */
    public static function pictureByAttachmentId(
        int $attachmentId,
        array $mqWithWidth = [],
        bool $pixelRatio2x = false,
        int|float|null $aspectRatio = null,
        string $imgAlt = '',
        bool $lazy = false,
        array $attrs = []
    ): string {
        $originUrl = (string)wp_get_attachment_image_url($attachmentId, 'full');

        return static::picture($originUrl, $mqWithWidth, $pixelRatio2x, $aspectRatio, $imgAlt, $lazy, $attrs);
    }


    /**
     * Responsive images' helper.
     * Create html tag Picture from attachment url.
     *
     * @param string         $originUrl Image original url (full-size).
     * @param array          $mqWithWidth Media query with image with for this query,
     *                            format ['metaQuery' => widthInPx(int), ..., defaultWidthInPx(int) ]
     * @param bool           $pixelRatio2x Whether to add images with 2x pixel ratio.
     * @param int|float|null $aspectRatio Image aspect ratio(width/height).
     * @param string         $imgAlt Image alt attribute string.
     * @param bool           $lazy Whether to apply lazy loading for the image.
     * @param array          $attrs Additional attributes, f.e. ['class'=>'add-class1']
     *
     * @return string Picture html tag.
     */
    public static function picture(
        string $originUrl,
        array $mqWithWidth = [],
        bool $pixelRatio2x = false,
        int|float|null $aspectRatio = null,
        string $imgAlt = '',
        bool $lazy = false,
        array $attrs = []
    ) {
        $pictureHtml = '';

        try {
            $imgAlt = esc_attr(strip_tags($imgAlt));
            $resizer = Resizer::makeWithUrl($originUrl);

            $sources = [];
            foreach ($mqWithWidth as $mediaQuery => $widthToResize) {
                $mediaQuery = is_string($mediaQuery) && ! empty($mediaQuery) ? $mediaQuery : '';
                $widthToResize = (int)$widthToResize;

                $resizer->setWidth($widthToResize);
                if ($aspectRatio) {
                    $resizer->setHeight(round($widthToResize / $aspectRatio));
                }

                $srcset = [];
                $srcset[] = SrcsetItem::makeWithResize($resizer, '1x', $lazy);

                if ($pixelRatio2x) {
                    $resizer->setWidth($widthToResize * 2);
                    if ($aspectRatio) {
                        $resizer->setHeight(round(($widthToResize * 2) / $aspectRatio));
                    }
                    $srcset[] = SrcsetItem::makeWithResize($resizer, '2x', $lazy);
                }

                $sources[] = Source::make($srcset, [], $mediaQuery);
            }

            $picture = Picture::make($originUrl, $imgAlt, null, null, $sources, $lazy);

            foreach ($attrs as $attrName => $attrValue) {
                $picture->setPictureAttr($attrName, $attrValue);
            }

            $pictureHtml = $picture->render();
        } catch (\Exception $ex) {
            error_log("\nFile: {$ex->getFile()}\nLine: {$ex->getLine()}\nMessage: {$ex->getMessage()}\n");
        }

        return $pictureHtml;
    }


    /**
     * Responsive images helper.
     * Create html tag Img from post thumbnail.
     *
     * @param int            $postId Post Id.
     * @param array          $mqWithWidth Media queries with width,
     *                           format: ['media-query' => width-in-px(int), ... , default-width-in-px(int) ]
     * @param bool           $pixelRatio2x Whether to add images with 2x pixel ratio.
     * @param int|float|null $aspectRatio Image aspect ratio(width/height).
     * @param bool           $lazy Lazy loading.
     * @param array          $attrs Additional attributes, f.e. ['class'=>'add-class1']
     *
     * @return string Image html tag.
     */
    public static function imgForPost(
        int $postId,
        array $mqWithWidth = [],
        bool $pixelRatio2x = false,
        int|float|null $aspectRatio = null,
        bool $lazy = false,
        array $attrs = []
    ) {
        if ( ! $postId || ! has_post_thumbnail($postId)) {
            return '';
        }

        $originUrl = get_the_post_thumbnail_url($postId, 'full');
        $imgAlt = esc_attr(strip_tags(get_the_title($postId)));

        return static::img($originUrl, $mqWithWidth, $pixelRatio2x, $aspectRatio, $imgAlt, $lazy, $attrs);
    }


    /**
     * Responsive images helper.
     * Create html tag Img by attachment Id.
     *
     * @param int            $attachmentId Attachment Id.
     * @param array          $mqWithWidth Media queries with width,
     *                           format: ['media-query' => width-in-px(int), ... , default-width-in-px(int) ]
     * @param bool           $pixelRatio2x Whether to add images with 2x pixel ratio.
     * @param int|float|null $aspectRatio Image aspect ratio(width/height).
     * @param string         $imgAlt Image alt attribute string.
     * @param bool           $lazy Lazy loading.
     * @param array          $attrs Additional attributes, f.e. ['class'=>'add-class1']
     *
     * @return string Image html tag.
     */
    public static function imgByAttachmentId(
        int $attachmentId,
        array $mqWithWidth = [],
        bool $pixelRatio2x = false,
        int|float|null $aspectRatio = null,
        string $imgAlt = '',
        bool $lazy = false,
        array $attrs = []
    ) {
        $originUrl = (string)wp_get_attachment_image_url($attachmentId, 'full');

        return static::img($originUrl, $mqWithWidth, $pixelRatio2x, $aspectRatio, $imgAlt, $lazy, $attrs);
    }


    /**
     * Responsive images' helper.
     * Create html tag Img from attachment url.
     *
     * @param string         $originUrl Image original url (full-size).
     * @param array          $mqWithWidth Media query with image with for this query,
     *                            format ['metaQuery' => widthInPx(int), ..., defaultWidthInPx(int) ]
     * @param bool           $pixelRatio2x Whether to add images with 2x pixel ratio.
     * @param int|float|null $aspectRatio Image aspect ratio(width/height).
     * @param string         $imgAlt Image alt attribute string.
     * @param bool           $lazy Whether to apply lazy loading for the image.
     * @param array          $attrs Additional attributes, f.e. ['class'=>'add-class1']
     *
     * @return string Image html tag.
     */
    public static function img(
        string $originUrl,
        array $mqWithWidth = [],
        bool $pixelRatio2x = false,
        int|float|null $aspectRatio = null,
        string $imgAlt = '',
        bool $lazy = false,
        array $attrs = []
    ) {
        $imgHtml = '';

        try {
            $imgAlt = esc_attr(strip_tags($imgAlt));
            $resizer = Resizer::makeWithUrl($originUrl);

            $sizes = $srcset = [];
            foreach ($mqWithWidth as $mediaQuery => $widthToResize) {
                $mediaQuery = is_string($mediaQuery) && ! empty($mediaQuery) ? $mediaQuery : '';
                $widthToResize = (int)$widthToResize;

                $sizes[] = Size::make($mediaQuery, "{$widthToResize}px");

                $resizer->setWidth($widthToResize);
                if ($aspectRatio) {
                    $resizer->setHeight(round($widthToResize / $aspectRatio));
                }

                $srcset[] = SrcsetItem::makeWithResize($resizer, "{$widthToResize}w", $lazy);

                if ($pixelRatio2x) {
                    $resizer->setWidth($widthToResize * 2);
                    if ($aspectRatio) {
                        $resizer->setHeight(round(($widthToResize * 2) / $aspectRatio));
                    }

                    $srcset[] = SrcsetItem::makeWithResize($resizer, ($widthToResize * 2) . 'w', $lazy);
                }
            }

            $img = Img::make($originUrl, $imgAlt, null, null, $srcset, $sizes, $lazy);

            foreach ($attrs as $attrName => $attrValue) {
                $img->setAttr($attrName, $attrValue);
            }

            $imgHtml = $img->render();
        } catch (\Exception $ex) {
            error_log("\nFile: {$ex->getFile()}\nLine: {$ex->getLine()}\nMessage: {$ex->getMessage()}\n");
        }

        return $imgHtml;
    }


    /**
     * Gets information for all registered WordPress image sizes. Even they was removed by filters.
     *
     * @return array Data for all registered image sizes (width, height, crop, name).
     */
    public static function getAllInitedImageSizes(): array
    {
        $sizes = [];

        $defaultSizes = ['thumbnail', 'medium', 'medium_large', 'large'];
        $additionalSizes = \wp_get_additional_image_sizes();

        if ( ! empty($additionalSizes)) {
            $allSizes = array_merge($defaultSizes, array_keys($additionalSizes));
        }

        foreach ($allSizes as $sizeName) {
            $sizes[$sizeName] = [
                'width'  => '',
                'height' => '',
                'crop'   => false,
                'name'   => $sizeName,
            ];

            $sizes[$sizeName]['width'] = isset($additionalSizes[$sizeName]['width'])
                ? (int)$additionalSizes[$sizeName]['width']  // // For theme-added sizes.
                : (int)get_option("{$sizeName}_size_w"); // For default sizes set in options.


            $sizes[$sizeName]['height'] = isset($additionalSizes[$sizeName]['height'])
                ? (int)$additionalSizes[$sizeName]['height']  // // For theme-added sizes.
                : (int)get_option("{$sizeName}_size_h"); // For default sizes set in options.


            $sizes[$sizeName]['crop'] = isset($additionalSizes[$sizeName]['crop'])
                ? (int)$additionalSizes[$sizeName]['crop']  // // For theme-added sizes.
                : (int)get_option("{$sizeName}_crop"); // For default sizes set in options.
        }

        return $sizes;
    }


    /**
     * Gets the WordPress image sizes (formatted strings).
     *
     * @return array A list of image sizes in the form of 'medium' => 'medium - 300 × 300'.
     */
    public static function getAllInitedImageSizesFormatted(): array
    {
        $sizes = static::getAllInitedImageSizes();

        foreach ($sizes as $sizeKey => $sizeData) {
            $sizes[$sizeKey] = sprintf(
                '%s - %d x %d (crop = %s)',
                esc_html(stripslashes($sizeData['name'])),
                $sizeData['width'],
                $sizeData['height'],
                $sizeData['crop']
            );
        }

        return $sizes;
    }


    /**
     * Check if attachment if SVG by Id.
     *
     * @param int $id Attachment Id.
     *
     * @return bool True if it is svg. False otherwise.
     */
    public static function isSvgByAttachmentId(int $id): bool
    {
        return $id > 0 && 'image/svg+xml' === \get_post_mime_type($id);
    }


    /**
     * Check if attachment if SVG by file extention of the attachment url.
     *
     * @param string $url Attachment url.
     *
     * @return bool True if it is svg. False otherwise.
     */
    public static function isSvgByAttachmentUrl(string $url): bool
    {
        if ($url) {
            $path = parse_url($url, PHP_URL_PATH);
            $extension = pathinfo($path, PATHINFO_EXTENSION);
            if ('svg' === strtolower($extension)) {
                return true;
            }
        }

        return false;
    }
}
