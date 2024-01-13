<?php

namespace WPRI\ResponsiveImages;

class Size
{

    /**
     * @var string Media query.
     */
    private string $media;

    /**
     * @var string Width of the slot for image.
     */
    private string $widthOfSlot;

    /**
     * Constructor.
     *
     * @param string $media Media query.
     * @param string $widthOfSlot Width of the slot for image.
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(string $media, string $widthOfSlot)
    {
        $this->guard($widthOfSlot);
        $this->media = $media;
        $this->widthOfSlot = $widthOfSlot;
    }


    /**
     * Checks parameters for correctness.
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    private function guard($widthOfSlot): void
    {
        if ( ! $widthOfSlot) {
            throw new \InvalidArgumentException('Size: Empty $widthOfSlot');
        }
    }


    /**
     * Static constructor.
     *
     * @param string $media Media query.
     * @param string $widthOfSlot Width of the slot for image.
     *
     * @return static
     * @throws \InvalidArgumentException
     */
    public static function make(string $media, string $widthOfSlot): static
    {
        return new static($media, $widthOfSlot);
    }


    /**
     * Renders size.
     *
     * @return string
     */
    public function render(): string
    {
        return $this->media ? "{$this->media} {$this->widthOfSlot}" : $this->widthOfSlot;
    }


    public function __toString()
    {
        return $this->render();
    }
}
