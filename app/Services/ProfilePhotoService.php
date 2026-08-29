<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

/**
 * Turns an uploaded image into a profile photo.
 *
 * Two pictures come out of one upload. The small square is what every
 * screen shows — a top bar, a row in a list — and is kept deliberately
 * tiny because a page full of them is a page full of downloads. The
 * source is the whole picture, optimised, kept so its owner can reframe
 * it later without going to find the file again.
 *
 * Security model: the uploaded bytes are never stored. Both outputs are
 * decoded with GD and completely RE-ENCODED as fresh JPEGs — stripping
 * EXIF/metadata and destroying any polyglot payload — with dimension and
 * size bombs rejected before decoding.
 */
class ProfilePhotoService
{
    /**
     * Edge length of the picture every screen shows.
     *
     * Drawn at 32-40px in a top bar or a list, so this is already twice
     * what a dense screen needs. Small matters more than sharp here: a
     * Users page carries one of these per row.
     */
    public const SIZE = 128;

    /**
     * Longest edge of the picture kept for reframing.
     */
    public const SOURCE_SIZE = 1024;

    /**
     * The stored photo must stay under MySQL's plain-BLOB ceiling.
     */
    private const MAX_STORED_BYTES = 60000;

    /**
     * The source lives in a MEDIUMBLOB and has room to be a photograph,
     * but not so much room that a row becomes expensive to read.
     */
    private const MAX_SOURCE_BYTES = 500000;

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
     * Re-encode an upload as the picture kept for reframing.
     *
     * The whole frame is kept — not a square — because the crop is
     * decided afterwards and can be decided again. Anything already
     * smaller than the ceiling is re-encoded rather than passed through:
     * that is what strips the metadata.
     *
     * @return array{0: string, 1: string}
     */
    public function processSource(string $bytes): array
    {
        $info = @getimagesizefromstring($bytes);

        if ($info === false) {
            $this->reject();
        }

        [$width, $height] = $info;

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

        $scale = min(
            1,
            self::SOURCE_SIZE / max($width, $height)
        );

        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));

        $target = imagecreatetruecolor($targetWidth, $targetHeight);

        $white = imagecolorallocate($target, 255, 255, 255);

        imagefill($target, 0, 0, $white);

        imagecopyresampled(
            $target,
            $source,
            0,
            0,
            0,
            0,
            $targetWidth,
            $targetHeight,
            $width,
            $height
        );

        imagedestroy($source);

        $encoded = null;

        foreach ([84, 76, 68, 60] as $quality) {
            ob_start();

            imagejpeg($target, null, $quality);

            $encoded = ob_get_clean();

            if (strlen($encoded) <= self::MAX_SOURCE_BYTES) {
                break;
            }
        }

        imagedestroy($target);

        if ($encoded === null || $encoded === '' || strlen($encoded) > self::MAX_SOURCE_BYTES) {
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
