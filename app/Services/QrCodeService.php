<?php

namespace App\Services;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class QrCodeService
{
    /**
     * Render an SVG QR code for the given payload.
     *
     * SVG output is used because it requires no GD/Imagick extension and can be
     * safely embedded inline (no credentials are ever placed in the payload).
     */
    public function svg(string $payload, int $size = 240, int $margin = 1): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle($size, $margin),
            new SvgImageBackEnd(),
        );

        return (new Writer($renderer))->writeString($payload);
    }

    public function dataUri(string $payload, int $size = 240): string
    {
        return 'data:image/svg+xml;base64,'.base64_encode($this->svg($payload, $size));
    }
}
