<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

/**
 * Turns an uploaded image into a small, safe, square profile photo.
 *
 * Security model: the uploaded bytes are never stored. The image is
 * decoded with GD and completely RE-ENCODED to a fresh 256x256 JPEG —
 * stripping EXIF/metadata and destroying any polyglot payload — with
 * dimension and size bombs rejected before decoding. Only the
 * re-encoded bytes are persisted.
 */
class ProfilePhotoService
{
    /**
     * Output edge length in pixels.
     */
    public const SIZE = 256;

    /**
     * The stored photo must stay under MySQL's plain-BLOB ceiling.
     */
    private const MAX_STORED_BYTES = 60000;

    /**
     * Reject images larger than this many pixels before decoding.
     */
    private const MAX_PIXELS = 25_000_000;

    /**
     * Process raw uploaded bytes into [jpegBytes, mime].
     *
     * @return array{0: string, 1: string}
     */
    public function process(string $bytes): array
    {
        $info = @getimagesizefromstring($bytes);

        if ($info === false) {
            $this->reject();
        }

        [$width, $height] = $info;

        $type = $info[2];

        /*
         * GD-decodable input only: JPEG, PNG, GIF, WEBP, BMP. (HEIC is
         * converted in the browser before upload; a raw HEIC body
         * fails getimagesizefromstring above.)
         */
        $allowed = [
            IMAGETYPE_JPEG,
            IMAGETYPE_PNG,
            IMAGETYPE_GIF,
            IMAGETYPE_WEBP,
            IMAGETYPE_BMP,
        ];

        if (! in_array($type, $allowed, true)) {
            $this->reject();
        }

        if (
            $width < 32
            || $height < 32
            || ($width * $height) > self::MAX_PIXELS
        ) {
            $this->reject();
        }

        $source = @imagecreatefromstring($bytes);

        if ($source === false) {
            $this->reject();
        }

        /*
         * Centre-crop to a square (the browser normally uploads a
         * pre-cropped square; direct API uploads still come out
         * square), then resample down.
         */
        $edge = min($width, $height);

        $sourceX = (int) floor(($width - $edge) / 2);
        $sourceY = (int) floor(($height - $edge) / 2);

        $target = imagecreatetruecolor(self::SIZE, self::SIZE);

        /*
         * Transparent PNG/GIF pixels land on white rather than black.
         */
        $white = imagecolorallocate($target, 255, 255, 255);

        imagefill($target, 0, 0, $white);

        imagecopyresampled(
            $target,
            $source,
            0,
            0,
            $sourceX,
            $sourceY,
            self::SIZE,
            self::SIZE,
            $edge,
            $edge
        );

        imagedestroy($source);

        /*
         * Encode as small as possible: step the JPEG quality down
         * until the result fits comfortably in a BLOB column.
         */
        $encoded = null;

        foreach ([82, 74, 66, 58, 50] as $quality) {
            ob_start();

            imagejpeg($target, null, $quality);

            $encoded = ob_get_clean();

            if (strlen($encoded) <= self::MAX_STORED_BYTES) {
                break;
            }
        }

        imagedestroy($target);

        if ($encoded === null || $encoded === '' || strlen($encoded) > self::MAX_STORED_BYTES) {
            $this->reject();
        }

        return [$encoded, 'image/jpeg'];
    }

    /**
     * Uniform, non-technical refusal.
     *
     * @return never
     */
    private function reject(): void
    {
        throw ValidationException::withMessages([
            'photo' => [
                __('api.profile.photo_invalid'),
            ],
        ]);
    }
}
