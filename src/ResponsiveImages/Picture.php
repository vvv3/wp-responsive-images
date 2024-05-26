<?php

namespace WPRI\ResponsiveImages;

use WPRI\ImgUtils;

class Picture
{

    /**
     * @var Source[] Array of sources.
     */
    private array $sources = [];

    /**
     * @var array Picture attributes.
     */
    private array $pictureAttrs = [];

    /**
     * @var array Image attributes.
     */
    private array $imgAttrs = [];

    /**
     * @var bool Image lazy loading.
     */
    private bool $lazy;

    /**
     * @var bool Is SVG Image.
     */
    private bool $isSvg;


    /**
     * Constructor.
     *
     * @param string   $imgSrc Image 'src' attribute.
     * @param string   $imgAlt Image 'alt' attribute.
     * @param int|null $width Image width.
     * @param int|null $height Image height.
     * @param Source[] $sources Array of sources.
     * @param bool     $lazy Adds image 'loading="lazy"' attribute.
     */
    public function __construct(
        string $imgSrc,
        string $imgAlt = '',
        ?int $width = null,
        ?int $height = null,
        array $sources = [],
        bool $lazy = false
    ) {
        $sources = array_filter($sources, function ($value) {
            return $value instanceof Source;
        });

        $this->guard($imgSrc);

        $this->imgAttrs['src'] = $imgSrc;
        $this->imgAttrs['alt'] = $imgAlt;

        $this->lazy = $lazy;
        if ($this->lazy) {
            $this->imgAttrs['loading'] = 'lazy';
        }

        $this->isSvg = ImgUtils::isSvgByAttachmentUrl($imgSrc);

        if (!$this->isSvg) {
            $this->sources = $sources;
        } else {
            $this->imgAttrs['data-is_svg'] = 1;
        }

        $this->resolveImageInfo($width, $height);
    }


    /**
     * Checks parameters for correctness.
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    private function guard($imgSrc)
    {
        if (!$imgSrc) {
            throw new \InvalidArgumentException('Picture: Empty $imgSrc');
        }
    }


    /**
     * Static constructor.
     *
     * @param string   $imgSrc Image 'src' attribute.
     * @param string   $imgAlt Image 'alt' attribute.
     * @param int|null $width Image width.
     * @param int|null $height Image height.
     * @param Source[] $sources Array of sources.
     * @param bool     $lazy Adds image 'loading="lazy"' attribute.
     *
     * @return static
     */
    public static function make(
        string $imgSrc,
        string $imgAlt = '',
        ?int $width = null,
        ?int $height = null,
        array $sources = [],
        bool $lazy = false
    ): static {
        return new static($imgSrc, $imgAlt, $width, $height, $sources, $lazy);
    }


    /**
     * Set Picture attribute.
     *
     * @param string          $attrName Attribute name.
     * @param string|int|null $attrValue , null - if no value, f.e. "hidden"
     *
     * @return static
     */
    public function setPictureAttr(string $attrName, string|int|null $attrValue): static
    {
        $this->pictureAttrs[strtolower($attrName)] = $attrValue;

        return $this;
    }


    /**
     * Set Img attribute, except 'src', 'srcset', 'sizes'.
     *
     * @param string          $attrName Attribute name.
     * @param string|int|null $attrValue , null - if no value, f.e. "hidden"
     *
     * @return static
     */
    public function setImgAttr(string $attrName, string|int|null $attrValue): static
    {
        if (in_array(strtolower($attrName), ['src', 'srcset', 'sizes'], true)) {
            return $this;
        }
        $this->imgAttrs[strtolower($attrName)] = $attrValue;

        return $this;
    }


    /**
     * Renders Picture tag content.
     *
     * @return string
     */
    public function render(): string
    {
        $sources = implode("\n\t", $this->sources);
        $sources = $sources ? "{$sources}\n\t" : '';

        $img = $this->renderImg();

        $attrs = apply_filters('WPRI/picture/attributes', $this->pictureAttrs);

        $tagContent = '';
        foreach ($attrs as $attrName => $attrValue) {
            $tagContent .= $attrValue !== null
                ? esc_attr($attrName) . '="' . esc_attr($attrValue) . '" '
                : esc_attr($attrName) . ' ';
        }
        $tagContent = rtrim($tagContent);


        return "<picture {$tagContent}>\n\t{$sources}{$img}\n</picture>";
    }


    /**
     * Renders Img tag content.
     *
     * @return string
     */
    public function renderImg(): string
    {
        $attrs = apply_filters('WPRI/picture/img/attributes', $this->imgAttrs);

        $tagContent = '';
        foreach ($attrs as $attrName => $attrValue) {
            if (empty($attrValue) && in_array($attrName, ['srcset', 'sizes'], true)) {
                continue;
            }
            $tagContent .= $attrValue !== null
                ? esc_attr($attrName) . '="' . esc_attr($attrValue) . '" '
                : esc_attr($attrName) . ' ';
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
        if ($width && $height) {
            $this->imgAttrs['width'] = $width;
            $this->imgAttrs['height'] = $height;
        } elseif (!$this->isSvg) {
            $attachInfo = ImgUtils::getAttachmentInfoByPath(ImgUtils::getAttachmentPathByUrl($this->imgAttrs['src']));
            $this->imgAttrs['width'] = $attachInfo['width'] ?: null;
            $this->imgAttrs['height'] = $attachInfo['height'] ?: null;
        }
    }
}
