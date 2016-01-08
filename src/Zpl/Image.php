<?php

namespace Zebra\Zpl;

class Image
{
    /**
     * The image identifier.
     *
     * @var resource
     */
    protected $image;

    /**
     * The ASCII hex data.
     *
     * @var string
     */
    protected $data;

    /**
     * Create an instance.
     *
     * @param string $image
     */
    public function __construct($image)
    {
        $this->image = imagecreatefromstring($image);

        $this->dither();
    }

    /**
     * Convert the image to 2 colours with dithering.
     */
    protected function dither()
    {
        if (!imageistruecolor($this->image)) {
            imagepalettetotruecolor($this->image);
        }

        imagefilter($this->image, IMG_FILTER_GRAYSCALE);
        imagetruecolortopalette($this->image, true, 2);
    }

    /**
     * The width in bytes.
     *
     * @return int
     */
    public function widthInBytes()
    {
        return (int)ceil($this->width() / 8);
    }

    /**
     * The width in pixels.
     *
     * @return int
     */
    public function width()
    {
        return imagesx($this->image);
    }

    /**
     * The height in pixels.
     *
     * @return int
     */
    public function height()
    {
        return imagesy($this->image);
    }

    /**
     * Get the bitmap for the image by looping over every pixel.
     *
     * @return string
     */
    protected function getData()
    {
        $rows = [];

        for ($row = 0; $row < $this->height(); $row++) {

            $bits = null;

            for ($column = 0; $column < $this->width(); $column++) {
                $bits .= imagecolorat($this->image, $column, $row) ? '0' : '1';
            }

            $rows[] = $this->bitsToBytes($bits);
        }

        return implode(null, $this->compress($rows));
    }

    /**
     * Convert a binary string to ASCII hexadecimal numbers, two digits per byte.
     *
     * @param string $bits
     * @return string
     */
    protected function bitsToBytes($bits)
    {
        $bytes = str_split($bits, 8);

        // Pad the last byte with zeros.
        $bytes[] = str_pad(array_pop($bytes), 8, '0');

        return implode(null, array_map([$this, 'binToHex'], $bytes));
    }

    /**
     * Convert binary byte to hex.
     *
     * @param string $byte
     * @return string
     */
    protected function binToHex($byte)
    {
        return sprintf('%02X', bindec($byte));
    }

    /**
     * Compress the ASCII hex data.
     *
     * @param array $rows
     * @return array
     */
    protected function compress(array $rows)
    {
        $lastRow = null;

        foreach ($rows as &$row) {
            if ($row == $lastRow) {
                $row = ':';
                continue;
            }

            $lastRow = $row;

            $row = $this->padWithComma($row);
            $row = $this->compressRepeatingCharacters($row);
        }

        return $rows;
    }

    /**
     * Replace trailing zeros with a comma.
     *
     * @param string $row
     * @return string
     */
    protected function padWithComma($row)
    {
        return preg_replace('/0+$/', ',', $row);
    }

    /**
     * Compress characters which repeat.
     *
     * @param string $row
     * @return string
     */
    protected function compressRepeatingCharacters($row)
    {
        $callback = function ($matches) {
            return chr(ord('F') + strlen($matches[0])) . substr($matches[0], 1, 1);
        };

        return preg_replace_callback('/(.)(\1{2,})/', $callback, $row);
    }

    /**
     * Get the instance as a string.
     *
     * @return string
     */
    public function __toString()
    {
        if (is_null($this->data)) {
            $this->data = $this->getData();
        }

        return $this->data;
    }

}