<?php

namespace WPRI\ResponsiveImages;

use WPRI\ImgUtils;

class Source
{

    /**
     * @var array Source attributes.
     */
    private array $attrs = [];


    /**
     * Constructor.
     *
     * @param SrcsetItem[] $srcset Srcset list.
     * @param Size[]       $sizes Sizes list.
     * @param string       $media Media query.
     * @param string       $type MIME media type of the image.
     */
    public function __construct(array $srcset, array $sizes = [], string $media = '', string $type = '')
    {
        $srcset = array_filter($srcset, function ($value) {
            return $value instanceof SrcsetItem;
        });
        $sizes = array_filter($sizes, function ($value) {
            return $value instanceof Size;
        });

        $this->guard($srcset);

        $this->attrs['media'] = $media;
        $this->attrs['type'] = $type;
        $this->attrs['sizes'] = implode(', ', $sizes);
        $this->resolveSrcset($srcset);
    }


    /**
     * Checks parameters for correctness.
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    private function guard($srcset): void
    {
        if ( ! $srcset) {
            throw new \InvalidArgumentException('Source: empty Srcset');
        }
    }


    /**
     * Static constructor.
     *
     * @param SrcsetItem[] $srcset Srcset list.
     * @param Size[]       $sizes Sizes list.
     * @param string       $media Media query.
     * @param string       $type MIME media type of the image.
     *
     * @return static
     */
    public static function make(
        array $srcset,
        array $sizes = [],
        string $media = '',
        string $type = '',
    ): static {
        return new static($srcset, $sizes, $media, $type);
    }


    /**
     * Set source attribute, except 'src', 'srcset', 'sizes'.
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
     * Renders source tag content.
     *
     * @return string
     */
    public function render(): string
    {
        $attrs = apply_filters('WPRI/source/attributes', $this->attrs);

        $tagContent = '';
        foreach ($attrs as $attrName => $attrValue) {
            if (empty($attrValue) && in_array($attrName, ['srcset', 'sizes', 'media', 'type'], true)) {
                continue;
            }
            $tagContent .= $attrValue !== null
                ? esc_attr($attrName) . '="' . esc_attr($attrValue) . '" '
                : esc_attr($attrName) . ' ';
        }
        $tagContent = rtrim($tagContent);

        return "<source {$tagContent}>";
    }


    public function __toString()
    {
        return $this->render();
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

        $srcset = array_unique($srcset);

        /* @var SrcsetItem $srcsetItem */
        foreach ($srcset as $srcsetItem) {
            $srcsetHtml .= $srcsetItem->render() . $separator;
        }
        $srcsetHtml = rtrim($srcsetHtml, $separator);
        if ($srcsetHtml) {
            $this->attrs['srcset'] = $srcsetHtml;
        }
    }
}
