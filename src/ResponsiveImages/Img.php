<?php

namespace WPRI\ResponsiveImages;

use WPRI\ImgUtils;

class Img
{

    /**
     * @var array Image attributes.
     */
    private array $attrs = [];

    /**
     * @var bool Image lazy loading.
     */
    private bool $lazy;


    /**
     * Constructor.
     *
     * @param string       $imgSrc Image 'src' attribute.
     * @param string       $imgAlt Image 'alt' attribute.
     * @param int|null     $width Image width.
     * @param int|null     $height Image height.
     * @param SrcsetItem[] $srcset Srcset list.
     * @param Size[]       $sizes Sizes list.
     * @param bool         $lazy Adds image 'loading="lazy"' attribute.
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(
        string $imgSrc,
        string $imgAlt = '',
        ?int $width = null,
        ?int $height = null,
        array $srcset = [],
        array $sizes = [],
        bool $lazy = false
    ) {
        $srcset = array_filter($srcset, function ($value) {
            return $value instanceof SrcsetItem;
        });
        $sizes = array_filter($sizes, function ($value) {
            return $value instanceof Size;
        });

        $this->guard($imgSrc);

        $this->attrs['src'] = $imgSrc;
        $this->attrs['alt'] = $imgAlt;
        $this->lazy = $lazy;
        if ($this->lazy) {
            $this->attrs['loading'] = 'lazy';
        }

        $isSvg = ImgUtils::isSvgByAttachmentUrl($imgSrc);
        if ( ! $isSvg) {
            $this->attrs['sizes'] = implode(', ', $sizes);
            $this->resolveImageInfo($width, $height);
            $this->resolveSrcset($srcset);
        } else {
            $this->attrs['data-is_svg'] = 1;
        }
    }


    /**
     * Checks parameters for correctness.
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    private function guard($imgSrc): void
    {
        if ( ! $imgSrc) {
            throw new \InvalidArgumentException('Img: Empty $imgSrc');
        }
    }


    /**
     * Static constructor.
     *
     * @param string       $imgSrc Image 'src' attribute.
     * @param string       $imgAlt Image 'alt' attribute.
     * @param int|null     $width Image width.
     * @param int|null     $height Image height.
     * @param SrcsetItem[] $srcset Srcset list.
     * @param Size[]       $size Sizes list.
     * @param bool         $lazy Adds image 'loading="lazy"' attribute.
     *
     * @return static
     * @throws \InvalidArgumentException
     */
    public static function make(
        string $imgSrc,
        string $imgAlt = '',
        ?int $width = null,
        ?int $height = null,
        array $srcset = [],
        array $sizes = [],
        bool $lazy = false
    ): static {
        return new static($imgSrc, $imgAlt, $width, $height, $srcset, $sizes, $lazy);
    }


    /**
     * Set image attribute, except 'src', 'srcset', 'sizes'.
     *
     * @param string          $attrName Attribute name.
     * @param string|int|null $attrValue , null - if no value, f.e. "hidden"
     *
     * @return static
     */
    public function setAttr(string $attrName, string|int|null $attrValue): static
    {
        if (in_array(strtolower($attrName), ['src', 'srcset', 'sizes'], true)) {
            return $this;
        }
        $this->attrs[strtolower($attrName)] = $attrValue;

        return $this;
    }


    /**
     * Renders image tag content.
     *
     * @return string
     */
    public function render(): string
    {
        $attrs = apply_filters('WPRI/img/attributes', $this->attrs);

        $tagContent = '';
        foreach ($attrs as $attrName => $attrValue) {
            if (empty($attrValue) && in_array($attrName, ['srcset', 'sizes'], true)) {
                continue;
            }
            $tagContent .= $attrValue !== null ? $attrName . '="' . esc_attr($attrValue) . '" ' : esc_attr($attrName) . '" ';
        }
        $tagContent = rtrim($tagContent);

        return "<img {$tagContent}>";
    }


    public function __toString()
    {
        return $this->render();
    }


    /**
     * Resolves image information( width, height ).
     *
     * @param int|null $width Image width.
     * @param int|null $height Image height.
     *
     * @return void
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    private function resolveImageInfo(?int $width = null, ?int $height = null): void
    {
        $this->attrs['width'] = $width ?: null;
        $this->attrs['height'] = $height ?: null;

        if (empty($this->attrs['width']) || empty($this->attrs['height'])) {
            $attachInfo = ImgUtils::getAttachmentInfoByPath(ImgUtils::getAttachmentPathByUrl($this->attrs['src']));

            $this->attrs['width'] = $attachInfo['width'] ?: null;
            $this->attrs['height'] = $attachInfo['height'] ?: null;
        }
    }


    /**
     * Resolve srcset items.
     *
     * @param SrcsetItem[] $srcset List of the srcset items.
     *
     * @return void
     */
    private function resolveSrcset(array $srcset): void
    {
        $srcsetHtml = '';
        $separator = ', ';

        /* @var SrcsetItem $srcsetItem */
        foreach ($srcset as $srcsetItem) {
            $srcsetHtml .= $srcsetItem->getDescriptor() ? "{$srcsetItem->getUrl()} {$srcsetItem->getDescriptor()}" : $srcsetItem->getUrl();
            $srcsetHtml .= $separator;
        }
        $srcsetHtml = rtrim($srcsetHtml, $separator);
        if ($srcsetHtml) {
            $this->attrs['srcset'] = $srcsetHtml;
        }
    }
}
