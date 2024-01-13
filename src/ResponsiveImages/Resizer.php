<?php

namespace WPRI\ResponsiveImages;

class Resizer
{

    /**
     * @var string|null Image source url.
     */
    private string|null $originUrl;

    /**
     * @var int|null Image pixel width.
     */
    private int|null $width;

    /**
     * @var int|null Image pixel height.
     */
    private int|null $height;

    /**
     * @var bool Image cropping behavior.
     */
    private bool $crop;

    /**
     * @var bool Resizes smaller image.
     */
    private bool $upscale;

    /**
     * Constructor.
     *
     * @param string   $originUrl Image source url.
     * @param int|null $width Image pixel width.
     * @param int|null $height Image pixel height.
     * @param bool     $crop Image cropping behavior.
     * @param bool     $upscale Resizes smaller image.
     */
    public function __construct(
        string $originUrl = null,
        ?int $width = null,
        ?int $height = null,
        bool $crop = true,
        bool $upscale = true
    ) {
        $this->originUrl = $originUrl;
        $this->width = $width;
        $this->height = $height;
        $this->crop = $crop;
        $this->upscale = $upscale;
    }


    /**
     * Make by url.
     *
     * @param null     $originUrl Image source url.
     * @param int|null $width Image pixel width.
     * @param int|null $height Image pixel height.
     * @param bool     $crop Image cropping behavior.
     * @param bool     $upscale Resizes smaller image.
     *
     * @return static
     */
    public static function makeWithUrl(
        string $originUrl,
        ?int $width = null,
        ?int $height = null,
        bool $crop = true,
        bool $upscale = true
    ) {
        return new static($originUrl, $width, $height, $crop, $upscale);
    }


    /**
     * Make by attachment Id.
     *
     * @param int      $attachmentId Attachment Id.
     * @param int|null $width Image pixel width.
     * @param int|null $height Image pixel height.
     * @param bool     $crop Image cropping behavior.
     * @param bool     $upscale Resizes smaller image.
     *
     * @return static
     */
    public static function makeByAttachmentId(
        int $attachmentId,
        ?int $width = null,
        ?int $height = null,
        bool $crop = true,
        bool $upscale = true
    ) {
        $originUrl = wp_get_attachment_image_url($attachmentId, 'full');

        return new static($originUrl, $width, $height, $crop, $upscale);
    }


    /**
     * Make by Post Id.
     *
     * @param int      $postId Post Id.
     * @param int|null $width Image pixel width.
     * @param int|null $height Image pixel height.
     * @param bool     $crop Image cropping behavior.
     * @param bool     $upscale Resizes smaller image.
     *
     * @return static
     */
    public static function makeByPostId(
        int $postId,
        ?int $width = null,
        ?int $height = null,
        bool $crop = true,
        bool $upscale = true
    ) {
        $originUrl = get_the_post_thumbnail_url($postId, 'full');

        return new  static($originUrl, $width, $height, $crop, $upscale);
    }


    /**
     * Resize process.
     *
     * @return array If resize success returns [img_url, width, height] or false on fail.
     * @throws \InvalidArgumentException
     * @throws \Aq_Exception
     */
    public function resize(): array
    {
        if ( ! $this->originUrl) {
            throw new \InvalidArgumentException('Resizer: originUrl is empty');
        }
        if ( ! $this->width) {
            throw new \InvalidArgumentException('Resizer: width is empty');
        }

        if ( ! function_exists('\aq_resize')) {
            require_once __DIR__ . '/../../libs/aq_resizer/aq_resizer.php';
        }

        return \aq_resize($this->originUrl, $this->width, $this->height, $this->crop, false, $this->upscale);
    }

    /**
     * @return string|null
     */
    public function getOriginUrl(): ?string
    {
        return $this->originUrl;
    }

    /**
     * @param string|null $originUrl
     *
     * @return Resizer
     */
    public function setOriginUrl(?string $originUrl): static
    {
        $this->originUrl = $originUrl;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getWidth(): ?int
    {
        return $this->width;
    }

    /**
     * @param int|null $width
     *
     * @return Resizer
     */
    public function setWidth(?int $width): static
    {
        $this->width = $width;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getHeight(): ?int
    {
        return $this->height;
    }

    /**
     * @param int|null $height
     *
     * @return Resizer
     */
    public function setHeight(?int $height): static
    {
        $this->height = $height;

        return $this;
    }

    /**
     * @return bool
     */
    public function getCrop(): bool
    {
        return $this->crop;
    }

    /**
     * @param bool $crop
     *
     * @return Resizer
     */
    public function setCrop(bool $crop): static
    {
        $this->crop = $crop;

        return $this;
    }

    /**
     * @return bool
     */
    public function getUpscale(): bool
    {
        return $this->upscale;
    }

    /**
     * @param bool $upscale
     *
     * @return Resizer
     */
    public function setUpscale(bool $upscale): static
    {
        $this->upscale = $upscale;

        return $this;
    }
}
