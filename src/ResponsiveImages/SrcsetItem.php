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
     * @var int|null Image pixel width.
     */
    private int|null $width;

    /**
     * @var int|null Image pixel height.
     */
    private int|null $height;

    /**
     * @var bool Image lazy loading.
     */
    private bool $lazy;


    /**
     * Constructor.
     *
     * @param string   $url Image source url.
     * @param string   $descriptor Descriptor.
     * @param int|null $width Image pixel width.
     * @param int|null $height Image pixel height.
     * @param bool     $lazy Image lazy loading.
     */
    public function __construct(
        string $url,
        string $descriptor = '',
        ?int $width = null,
        ?int $height = null,
        bool $lazy = false
    ) {
        $this->guard($url, $descriptor);
        $this->url = $url;
        $this->descriptor = $descriptor;
        $this->lazy = $lazy;

        $isSvg = ImgUtils::isSvgByAttachmentUrl($url);
        if ( ! $isSvg) {
            $this->resolveImageInfo($width, $height);
        }
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
     * @return int|null
     */
    public function getWidth(): ?int
    {
        return $this->width;
    }

    /**
     * @return int|null
     */
    public function getHeight(): ?int
    {
        return $this->height;
    }


    /**
     * Static constructor
     *
     * @param string   $url Image source url.
     * @param string   $descriptor Descriptor.
     * @param int|null $width Image pixel width.
     * @param int|null $height Image pixel height.
     * @param bool     $lazy Image lazy loading.
     *
     * @return static
     */
    public static function make(
        string $url,
        string $descriptor = '',
        ?int $width = null,
        ?int $height = null,
        bool $lazy = false
    ): static {
        return new static($url, $descriptor, $width, $height, $lazy);
    }


    /**
     * Make with resize.
     *
     * @param Resizer $resizer Resizer.
     * @param string  $descriptor Descriptor.
     * @param bool    $lazy Image lazy loading.
     *
     * @return static
     */
    public static function makeWithResize(Resizer $resizer, string $descriptor = '', bool $lazy = false): static
    {
        $isSvg = ImgUtils::isSvgByAttachmentUrl($resizer->getOriginUrl());
        if ($isSvg) {
            return new static($resizer->getOriginUrl(), $descriptor, null, null, $lazy);
        }
        $resultData = $resizer->resize();
        $resultUrl = ! empty($resultData[0]) ? $resultData[0] : null;
        $resultWidth = ! empty($resultData[1]) ? $resultData[1] : null;
        $resultHeight = ! empty($resultData[2]) ? $resultData[2] : null;

        return new static($resultUrl, $descriptor, $resultWidth, $resultHeight, $lazy);
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
        $this->width = $width ?: null;
        $this->height = $height ?: null;

        if (empty($this->width) || empty($this->height)) {
            $attachInfo = ImgUtils::getAttachmentInfoByPath(ImgUtils::getAttachmentPathByUrl($this->url));
            $this->width = $attachInfo['width'] ?: null;
            $this->height = $attachInfo['height'] ?: null;
        }
    }
}
