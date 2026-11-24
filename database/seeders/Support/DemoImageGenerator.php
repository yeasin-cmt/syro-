<?php

namespace Database\Seeders\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Generates original, copyright-free catalogue imagery with GD:
 * minimalist garment silhouettes on soft studio backgrounds, plus abstract banners.
 * Shapes are drawn at 2Ã— and downsampled for smooth edges.
 */
class DemoImageGenerator
{
    /** Normalised polygons (x, y in 0..1) on a 4:5 canvas. */
    protected const SHAPES = [
        'tee' => [[.38, .17], [.22, .21], [.07, .36], [.17, .45], [.27, .38], [.27, .86], [.73, .86], [.73, .38], [.83, .45], [.93, .36], [.78, .21], [.62, .17], [.56, .22], [.5, .235], [.44, .22]],
        'shirt' => [[.39, .15], [.23, .19], [.15, .30], [.09, .78], [.19, .79], [.26, .42], [.27, .88], [.73, .88], [.74, .42], [.81, .79], [.91, .78], [.85, .30], [.77, .19], [.61, .15], [.5, .26]],
        'panjabi' => [[.40, .12], [.25, .16], [.17, .27], [.11, .74], [.20, .75], [.28, .38], [.26, .93], [.74, .93], [.72, .38], [.80, .75], [.89, .74], [.83, .27], [.75, .16], [.60, .12], [.5, .22]],
        'dress' => [[.40, .13], [.34, .16], [.35, .40], [.18, .89], [.82, .89], [.65, .40], [.66, .16], [.60, .13], [.5, .21]],
        'top' => [[.36, .20], [.26, .24], [.24, .40], [.30, .74], [.70, .74], [.76, .40], [.74, .24], [.64, .20], [.5, .30]],
        'saree' => [[.42, .12], [.30, .18], [.24, .50], [.20, .90], [.80, .90], [.70, .52], [.74, .22], [.60, .12], [.52, .20], [.66, .34], [.40, .30]],
        'pants' => [[.30, .13], [.70, .13], [.75, .89], [.56, .89], [.5, .37], [.44, .89], [.25, .89]],
        'sneaker' => [[.10, .63], [.12, .52], [.27, .48], [.42, .41], [.55, .43], [.67, .53], [.86, .58], [.91, .66], [.90, .70], [.10, .70]],
        'bag' => [[.20, .40], [.80, .40], [.84, .84], [.16, .84]],
        'cap' => [[.22, .58], [.26, .44], [.36, .35], [.50, .32], [.64, .35], [.74, .44], [.78, .58], [.92, .60], [.92, .64], [.22, .64]],
    ];

    protected const BACKGROUNDS = ['#EDEBE6', '#E8E4DC', '#EFEDE9', '#E5E2DB', '#ECE7DF'];

    public function product(string $shape, string $hex, string $path, int $variant = 0): string
    {
        $w = 800; $h = 1000; $s = 2;
        $img = imagecreatetruecolor($w * $s, $h * $s);

        // Studio background with a soft vertical falloff.
        $bg = $this->rgb(self::BACKGROUNDS[($variant + crc32($path)) % count(self::BACKGROUNDS)]);
        $this->verticalGradient($img, $bg, $this->shade($bg, -0.06));

        $color = $this->rgb($hex);
        $scale = $variant === 1 ? 0.86 : 1.0;   // second image: slightly pulled back
        $offsetY = $variant === 1 ? 0.05 : 0.0;

        // Floor shadow, anchored just below the lowest point of the shape.
        $baseY = match ($shape) {
            'watch', 'sunglasses' => .5 + .38 * $scale,
            'sneaker' => .75,
            default => max(array_column(self::SHAPES[$shape] ?? self::SHAPES['tee'], 1)),
        };
        $baseY = (($baseY - .5) * $scale) + .5 + $offsetY + .02;
        $shadow = imagecolorallocatealpha($img, 0, 0, 0, 112);
        imagefilledellipse($img, (int) ($w * $s * .5), (int) ($h * $s * $baseY), (int) ($w * $s * .62 * $scale), (int) ($h * $s * .03), $shadow);

        if ($shape === 'watch') {
            $this->drawWatch($img, $w * $s, $h * $s, $color, $scale);
        } elseif ($shape === 'sunglasses') {
            $this->drawSunglasses($img, $w * $s, $h * $s, $color, $scale);
        } else {
            $points = [];
            foreach (self::SHAPES[$shape] ?? self::SHAPES['tee'] as [$x, $y]) {
                $points[] = (int) (((($x - .5) * $scale) + .5) * $w * $s);
                $points[] = (int) (((($y - .5) * $scale) + .5 + $offsetY) * $h * $s);
            }
            $fill = imagecolorallocate($img, ...$color);
            imagefilledpolygon($img, $points, $fill);

            // A subtle placket / seam on garments to give the flat shape some depth.
            if (in_array($shape, ['tee', 'shirt', 'panjabi', 'dress', 'top'], true)) {
                $hi = imagecolorallocatealpha($img, 255, 255, 255, 110);
                imagesetthickness($img, 3 * $s);
                imageline($img, (int) ($w * $s * .5), (int) ($h * $s * (.30 * $scale + .5 - .5 * $scale + $offsetY)),
                    (int) ($w * $s * .5), (int) ($h * $s * (.78 * $scale + .5 - .5 * $scale + $offsetY)), $hi);
            }

            if ($shape === 'bag') {
                $handle = imagecolorallocate($img, ...$this->shade($color, -0.25));
                imagesetthickness($img, 14 * $s);
                imagearc($img, (int) ($w * $s * .5), (int) ($h * $s * (.40 + $offsetY)), (int) ($w * $s * .36 * $scale), (int) ($h * $s * .30 * $scale), 180, 360, $handle);
            }
            if ($shape === 'sneaker') {
                $isLight = array_sum($color) > 600;
                $sole = imagecolorallocate($img, ...($isLight ? [60, 58, 55] : [245, 243, 238]));
                $y1 = (.68 - .5) * $scale + .5 + $offsetY;
                $y2 = (.73 - .5) * $scale + .5 + $offsetY;
                $x1 = (.10 - .5) * $scale + .5;
                $x2 = (.91 - .5) * $scale + .5;
                imagefilledrectangle($img, (int) ($w * $s * $x1), (int) ($h * $s * $y1), (int) ($w * $s * $x2), (int) ($h * $s * $y2), $sole);
                // Lace detail.
                $lace = imagecolorallocatealpha($img, ...[...($isLight ? [60, 58, 55] : [245, 243, 238]), 60]);
                imagesetthickness($img, 4 * $s);
                foreach ([.40, .46, .52] as $lx) {
                    $px = ((($lx - .5) * $scale) + .5) * $w * $s;
                    imageline($img, (int) $px, (int) ($h * $s * ((.47 - .5) * $scale + .5 + $offsetY)), (int) ($px + 18 * $s), (int) ($h * $s * ((.52 - .5) * $scale + .5 + $offsetY)), $lace);
                }
            }
        }

        return $this->save($img, $w, $h, $s, $path);
    }

    public function banner(array $from, array $to, string $path, int $w = 1920, int $h = 1000): string
    {
        $img = imagecreatetruecolor($w, $h);
        for ($x = 0; $x < $w; $x++) {
            $t = $x / $w;
            $c = imagecolorallocate($img, ...array_map(fn ($a, $b) => (int) ($a + ($b - $a) * $t), $from, $to));
            imageline($img, $x, 0, $x, $h, $c);
        }

        // Editorial geometry: concentric arcs and a fine accent line.
        $accent = imagecolorallocatealpha($img, 182, 154, 112, 70);
        imagesetthickness($img, 2);
        for ($i = 0; $i < 6; $i++) {
            imageellipse($img, (int) ($w * .78), (int) ($h * .55), (int) ($h * (.5 + $i * .22)), (int) ($h * (.5 + $i * .22)), $accent);
        }
        $solid = imagecolorallocatealpha($img, 182, 154, 112, 30);
        imagefilledellipse($img, (int) ($w * .78), (int) ($h * .55), (int) ($h * .42), (int) ($h * .42), $solid);
        imagesetthickness($img, 1);
        imageline($img, (int) ($w * .06), (int) ($h * .9), (int) ($w * .94), (int) ($h * .9), $accent);

        ob_start();
        imagejpeg($img, null, 86);
        Storage::disk('public')->put($path, ob_get_clean());

        return $path;
    }

    /* ------------------------------------------------------------------ */

    protected function drawWatch($img, int $W, int $H, array $color, float $scale): void
    {
        $band = imagecolorallocate($img, ...$this->shade($color, -0.15));
        imagefilledrectangle($img, (int) ($W * .43), (int) ($H * (.5 - .36 * $scale)), (int) ($W * .57), (int) ($H * (.5 + .36 * $scale)), $band);
        $case = imagecolorallocate($img, 200, 190, 170);
        $r = (int) ($W * .40 * $scale);
        imagefilledellipse($img, (int) ($W * .5), (int) ($H * .5), $r, $r, $case);
        $face = imagecolorallocate($img, ...$color);
        imagefilledellipse($img, (int) ($W * .5), (int) ($H * .5), (int) ($r * .86), (int) ($r * .86), $face);
        $hand = imagecolorallocate($img, 240, 236, 228);
        imagesetthickness($img, 6);
        imageline($img, (int) ($W * .5), (int) ($H * .5), (int) ($W * .5), (int) ($H * .5 - $r * .32), $hand);
        imageline($img, (int) ($W * .5), (int) ($H * .5), (int) ($W * .5 + $r * .24), (int) ($H * .5 + $r * .08), $hand);
    }

    protected function drawSunglasses($img, int $W, int $H, array $color, float $scale): void
    {
        $lens = imagecolorallocate($img, ...$color);
        $r = (int) ($W * .30 * $scale);
        imagefilledellipse($img, (int) ($W * .32), (int) ($H * .5), $r, (int) ($r * .82), $lens);
        imagefilledellipse($img, (int) ($W * .68), (int) ($H * .5), $r, (int) ($r * .82), $lens);
        imagesetthickness($img, 10);
        imagearc($img, (int) ($W * .5), (int) ($H * .5), (int) ($W * .12), (int) ($H * .06), 200, 340, $lens);
    }

    protected function verticalGradient($img, array $top, array $bottom): void
    {
        $h = imagesy($img); $w = imagesx($img);
        for ($y = 0; $y < $h; $y++) {
            $t = $y / $h;
            $c = imagecolorallocate($img, ...array_map(fn ($a, $b) => (int) ($a + ($b - $a) * $t), $top, $bottom));
            imageline($img, 0, $y, $w, $y, $c);
        }
    }

    protected function save($img, int $w, int $h, int $s, string $path): string
    {
        $out = imagecreatetruecolor($w, $h);
        imagecopyresampled($out, $img, 0, 0, 0, 0, $w, $h, $w * $s, $h * $s);
        ob_start();
        imagejpeg($out, null, 85);
        Storage::disk('public')->put($path, ob_get_clean());

        return $path;
    }

    protected function rgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }

    protected function shade(array $rgb, float $amount): array
    {
        return array_map(fn ($c) => max(0, min(255, (int) ($amount < 0 ? $c * (1 + $amount) : $c + (255 - $c) * $amount))), $rgb);
    }
}
