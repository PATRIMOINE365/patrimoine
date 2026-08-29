<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ProfilePhotoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\Concerns\AuthenticatesApiUser;
use Tests\TestCase;

/**
 * V1.0.31 keeps the picture, not only the crop of it.
 *
 * Until now the only thing stored was the small square the browser cut
 * out, so changing your mind about the framing meant finding the original
 * file again. Both are stored now, along with where the frame sat.
 */
class ProfilePhotoSourceTest extends TestCase
{
    use AuthenticatesApiUser;
    use RefreshDatabase;

    private User $me;

    protected function setUp(): void
    {
        parent::setUp();

        $this->me = $this->authenticateApiUser();
    }

    /**
     * A JPEG of the given size, as a browser would upload one.
     */
    private function picture(int $width, int $height = 0): UploadedFile
    {
        $height = $height ?: $width;

        $image = imagecreatetruecolor($width, $height);

        /*
         * A flat colour compresses to almost nothing, which would make a
         * size assertion meaningless; noise is closer to a photograph.
         */
        for ($x = 0; $x < $width; $x += 4) {
            for ($y = 0; $y < $height; $y += 4) {
                imagefilledrectangle(
                    $image,
                    $x,
                    $y,
                    $x + 3,
                    $y + 3,
                    imagecolorallocate(
                        $image,
                        ($x * 7) % 256,
                        ($y * 13) % 256,
                        (($x + $y) * 3) % 256
                    )
                );
            }
        }

        ob_start();

        imagejpeg($image, null, 92);

        $bytes = ob_get_clean();

        imagedestroy($image);

        $path = tempnam(sys_get_temp_dir(), 'pm').'.jpg';

        file_put_contents($path, $bytes);

        return new UploadedFile($path, 'photo.jpg', 'image/jpeg', null, true);
    }

    public function test_both_the_square_and_the_picture_behind_it_are_kept(): void
    {
        $this
            ->post('/api/auth/me/avatar', [
                'photo' => $this->picture(512),
                'source' => $this->picture(1800, 1200),
                'crop' => '{"x":0.4,"y":0.6,"zoom":1.5}',
            ])
            ->assertOk();

        $user = User::query()->firstOrFail();

        $this->assertNotNull($user->profile_photo);

        $this->assertNotNull($user->profile_photo_source);

        $this->assertSame(
            '{"x":0.4,"y":0.6,"zoom":1.5}',
            $user->profile_photo_crop
        );

        /*
         * The small one is what every screen loads, so it stays small.
         */
        $small = imagecreatefromstring($user->profile_photo);

        $this->assertSame(ProfilePhotoService::SIZE, imagesx($small));

        $this->assertLessThan(60000, strlen($user->profile_photo));

        /*
         * The source keeps its shape — the crop is decided afterwards and
         * can be decided again — and is bounded on its longest edge.
         */
        $source = imagecreatefromstring($user->profile_photo_source);

        $this->assertSame(ProfilePhotoService::SOURCE_SIZE, imagesx($source));

        $this->assertSame(
            (int) round(1200 * (ProfilePhotoService::SOURCE_SIZE / 1800)),
            imagesy($source)
        );

        $this->assertLessThan(500000, strlen($user->profile_photo_source));
    }

    public function test_the_picture_comes_back_for_its_owner_to_reframe(): void
    {
        $this->post('/api/auth/me/avatar', [
            'photo' => $this->picture(512),
            'source' => $this->picture(900),
            'crop' => '{"x":0.5,"y":0.5,"zoom":2}',
        ])->assertOk();

        $this
            ->getJson('/api/auth/me/avatar/source')
            ->assertOk()
            ->assertJsonPath('crop', '{"x":0.5,"y":0.5,"zoom":2}')
            ->assertJson(
                fn ($json) => $json
                    ->whereType('source', 'string')
                    ->etc()
            );
    }

    public function test_there_is_nothing_to_reframe_before_a_photograph_exists(): void
    {
        $this
            ->getJson('/api/auth/me/avatar/source')
            ->assertOk()
            ->assertJsonPath('source', null)
            ->assertJsonPath('crop', null);
    }

    public function test_an_upload_without_the_picture_behind_it_still_works(): void
    {
        /*
         * A direct API call, or a client older than V1.0.31, sends only
         * the square. The photograph works; it simply cannot be reframed.
         */
        $this
            ->post('/api/auth/me/avatar', [
                'photo' => $this->picture(512),
            ])
            ->assertOk();

        $user = User::query()->firstOrFail();

        $this->assertNotNull($user->profile_photo);

        $this->assertNull($user->profile_photo_source);
    }

    public function test_removing_the_photograph_removes_the_picture_too(): void
    {
        $this->post('/api/auth/me/avatar', [
            'photo' => $this->picture(512),
            'source' => $this->picture(900),
            'crop' => '{"x":0.5,"y":0.5,"zoom":1}',
        ])->assertOk();

        $this->deleteJson('/api/auth/me/avatar')->assertOk();

        $user = User::query()->firstOrFail();

        $this->assertNull($user->profile_photo);

        $this->assertNull($user->profile_photo_source);

        $this->assertNull($user->profile_photo_crop);
    }

    public function test_the_stored_bytes_never_leak_through_the_model(): void
    {
        $this->post('/api/auth/me/avatar', [
            'photo' => $this->picture(512),
            'source' => $this->picture(900),
            'crop' => '{"x":0.5,"y":0.5,"zoom":1}',
        ])->assertOk();

        $serialised = User::query()->firstOrFail()->toArray();

        foreach ([
            'profile_photo',
            'profile_photo_mime',
            'profile_photo_source',
            'profile_photo_source_mime',
            'profile_photo_crop',
        ] as $column) {
            $this->assertArrayNotHasKey($column, $serialised);
        }
    }

    public function test_the_users_list_carries_a_face_for_each_row(): void
    {
        /*
         * Reading the Users page is an administrator's job — it is where
         * telling one colleague from another actually matters.
         */
        $this->me = $this->authenticateApiUser('administrator');

        $this->post('/api/auth/me/avatar', [
            'photo' => $this->picture(512),
        ])->assertOk();

        $rows = $this
            ->getJson('/api/users')
            ->assertOk()
            ->json('data');

        $this->assertNotEmpty($rows);

        foreach ($rows as $row) {
            $this->assertArrayHasKey(
                'avatar',
                $row,
                'Every row needs the key, so a row without a photograph '
                .'renders its initials rather than nothing.'
            );

            $this->assertArrayNotHasKey('profile_photo', $row);
        }

        $mine = collect($rows)->firstWhere('id', $this->me->id);

        $this->assertStringStartsWith(
            'data:image/jpeg;base64,',
            $mine['avatar']
        );
    }
}
