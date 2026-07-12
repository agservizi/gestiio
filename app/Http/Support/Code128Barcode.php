<?php

namespace App\Http\Support;

use InvalidArgumentException;
use RuntimeException;

class Code128Barcode
{
  private const START_B = 104;

  private const STOP = 106;

  /** @var list<string> */
  private const PATTERNS = [
    '212222', '222122', '222221', '121223', '121322', '131222', '122213', '122312', '132212', '221213',
    '221312', '231212', '112232', '122132', '122231', '113222', '123122', '123221', '223211', '221132',
    '221231', '213212', '223112', '312131', '311222', '321122', '321221', '312212', '322112', '322211',
    '212123', '212321', '232121', '111323', '131123', '131321', '112313', '132113', '132311', '211313',
    '231113', '231311', '112133', '112331', '132131', '113123', '113321', '133121', '313121', '211331',
    '231131', '213113', '213311', '213131', '311123', '311321', '331121', '312113', '312311', '332111',
    '314111', '221411', '431111', '111224', '111422', '121124', '121421', '141122', '141221', '112214',
    '112412', '122114', '122411', '142112', '142211', '241211', '221114', '413111', '241112', '134111',
    '111242', '121142', '121241', '114212', '124112', '124211', '411212', '421112', '421211', '212141',
    '214121', '412121', '111143', '111341', '131141', '114113', '114311', '411113', '411311', '113141',
    '114131', '311141', '411131', '211412', '211214', '211232', '2331112',
  ];

  public static function dataUri(
    string $text,
    int $barHeight = 54,
    int $moduleWidth = 2,
    int $quietZone = 12
  ): string {
    $text = trim($text);
    if ($text === '') {
      return self::blankDataUri($barHeight);
    }

    if (! extension_loaded('gd')) {
      throw new RuntimeException('Estensione GD richiesta per generare il barcode.');
    }

    $pattern = self::buildPattern($text);
    $width = self::patternWidth($pattern, $moduleWidth) + ($quietZone * 2);
    $image = imagecreatetruecolor(max(1, $width), max(1, $barHeight));

    if ($image === false) {
      throw new RuntimeException('Impossibile creare immagine barcode.');
    }

    $white = imagecolorallocate($image, 255, 255, 255);
    $black = imagecolorallocate($image, 0, 0, 0);
    imagefill($image, 0, 0, $white);

    $x = $quietZone;
    $drawBar = true;

    foreach (str_split($pattern) as $module) {
      $moduleSize = (int) $module * $moduleWidth;
      if ($drawBar) {
        imagefilledrectangle($image, $x, 0, $x + $moduleSize - 1, $barHeight - 1, $black);
      }
      $x += $moduleSize;
      $drawBar = ! $drawBar;
    }

    ob_start();
    imagepng($image);
    $png = ob_get_clean() ?: '';
    imagedestroy($image);

    return 'data:image/png;base64,'.base64_encode($png);
  }

  private static function buildPattern(string $text): string
  {
    $codes = [self::START_B];
    $length = strlen($text);

    for ($i = 0; $i < $length; $i++) {
      $ascii = ord($text[$i]);
      if ($ascii < 32 || $ascii > 126) {
        throw new InvalidArgumentException('Il barcode supporta solo caratteri ASCII stampabili.');
      }
      $codes[] = $ascii - 32;
    }

    $checksum = self::START_B;
    for ($i = 0; $i < count($codes); $i++) {
      if ($i === 0) {
        continue;
      }
      $checksum += $codes[$i] * $i;
    }
    $codes[] = $checksum % 103;
    $codes[] = self::STOP;

    $pattern = '';
    foreach ($codes as $code) {
      $pattern .= self::PATTERNS[$code] ?? '';
    }

    return $pattern;
  }

  private static function patternWidth(string $pattern, int $moduleWidth): int
  {
    $width = 0;
    foreach (str_split($pattern) as $module) {
      $width += (int) $module * $moduleWidth;
    }

    return $width;
  }

  private static function blankDataUri(int $height): string
  {
    $image = imagecreatetruecolor(1, max(1, $height));
    $white = imagecolorallocate($image, 255, 255, 255);
    imagefill($image, 0, 0, $white);
    ob_start();
    imagepng($image);
    $png = ob_get_clean() ?: '';
    imagedestroy($image);

    return 'data:image/png;base64,'.base64_encode($png);
  }
}
