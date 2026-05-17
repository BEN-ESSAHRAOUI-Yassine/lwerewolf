<?php

namespace App\Helpers;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class QrHelper
{
    public static function generate(string $data): string
    {
        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_MARKUP_SVG,
            'svgMultiplier' => 1,
            'svgAddXmlHeader' => false,
            'eccLevel' => QRCode::ECC_M,
            'scale' => 8,
        ]);

        $qrcode = new QRCode($options);

        return $qrcode->render($data);
    }
}
