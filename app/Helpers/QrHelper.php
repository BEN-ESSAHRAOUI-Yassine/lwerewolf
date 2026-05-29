<?php

namespace App\Helpers;

use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class QrHelper
{
    public static function generate(string $data): string
    {
        $options = new QROptions([
            'svgAddXmlHeader' => false,
            'eccLevel' => EccLevel::M,
            'scale' => 8,
        ]);

        $qrcode = new QRCode($options);

        return $qrcode->render($data);
    }
}
