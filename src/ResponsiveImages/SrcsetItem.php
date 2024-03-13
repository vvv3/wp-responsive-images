<?php

namespace WPRI\ResponsiveImages;

use WPRI\ImgUtils;

class SrcsetItem
{

    /**
     * @var string Image source url.
     */
    private string $url;

    /**
     * @var string  Descriptor, one of:
     *  - A width descriptor -  width in pixels (480w) — note that this uses the 'w' unit, not 'px';
     *  - A pixel density descriptor (2x).
     */
    private string $descriptor;

    /**
     * Constructor.
     *
     * @param string   $url Image source url.
     * @param string   $descriptor Descriptor.
     */
    public function __construct(string $url, string $descriptor = '') {
        $this->guard($url, $descriptor);
        $this->url = $url;
        $this->descriptor = $descriptor;
    }


    /**
     * Checks parameters for correctness.
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    private function guard($url, $descriptor)
    {
        if ( ! $url) {
            throw new \InvalidArgumentException('Srcset item: empty $url');
        }

        if ( ! \str_ends_with($descriptor, 'w') && ! \str_ends_with($descriptor, 'x')) {
            throw new \InvalidArgumentException('Srcset item: $descriptor must ends with "w" or "x"');
        }
    }


    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }


    /**
     * @return string
     */
    public function getDescriptor(): string
    {
        return $this->descriptor;
    }


    /**
     * Static constructor
     *
     * @param string   $url Image source url.
     * @param string   $descriptor Descriptor.
     *
     * @return static
     */
    public static function make(string $url, string $descriptor = ''): static {
        return new static($url, $descriptor);
    }


    /**
     * Make with resize.
     *
     * @param Resizer $resizer Resizer.
     * @param string  $descriptor Descriptor.
     *
     * @return static
     */
    public static function makeWithResize(Resizer $resizer, string $descriptor = ''): static
    {
        $isSvg = ImgUtils::isSvgByAttachmentUrl($resizer->getOriginUrl());
        if ($isSvg) {
            return new static($resizer->getOriginUrl(), $descriptor);
        }
        $resultData = $resizer->resize();
        $resultUrl = ! empty($resultData[0]) ? $resultData[0] : null;

        return new static($resultUrl, $descriptor);
    }


    /**
     * Renders srcset item.
     *
     * @return string
     */
    public function render(): string
    {
        return $this->descriptor ? "{$this->url} {$this->descriptor}" : $this->url;
    }


    public function __toString(): string
    {
        return $this->render();
    }
}
