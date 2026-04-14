<?php

namespace App\Services;

use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class QrCodeService
{
    /**
     * Generate a QR code as SVG markup.
     */
    public function generateSvg(string $url, int $scale = 5): string
    {
        $options = new QROptions([
            'outputType' => QROutputInterface::MARKUP_SVG,
            'eccLevel' => EccLevel::M,
            'scale' => $scale,
            'addQuietzone' => true,
            'outputBase64' => false,
        ]);

        return (new QRCode($options))->render($url);
    }

    /**
     * Generate a QR code as PNG binary data.
     */
    public function generatePng(string $url, int $scale = 10): string
    {
        $options = new QROptions([
            'outputType' => QROutputInterface::GDIMAGE_PNG,
            'eccLevel' => EccLevel::M,
            'scale' => $scale,
            'addQuietzone' => true,
            'imageTransparent' => false,
            'outputBase64' => false,
        ]);

        return (new QRCode($options))->render($url);
    }
}
