<?php

namespace Tests\Feature;

use App\Services\ProfilePhotoService;
use App\Models\Organisation;
use App\Models\User;
use App\Support\OrganisationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * V1.0.11 profile photos and profile self-service.
 */
class ProfilePhotoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A real PNG produced by GD, oversized so the server-side resize
     * and re-encode is exercised.
     */
    private function pngUpload(int $width = 640, int $height = 480): UploadedFile
    {
        $image = imagecreatetruecolor($width, $height);

        $paint = imagecolorallocate($image, 40, 120, 90);

        imagefilledellipse(
            $image,
            (int) ($width / 2),
            (int) ($height / 2),
            (int) ($width / 2),
            (int) ($height / 2),
            $paint
        );

        ob_start();

        imagepng($image);

        $bytes = ob_get_clean();

        imagedestroy($image);

        $path = tempnam(sys_get_temp_dir(), 'avatar').'.png';

        file_put_contents($path, $bytes);

        return new UploadedFile($path, 'avatar.png', 'image/png', null, true);
    }

    public function test_photo_is_reencoded_stored_small_and_served_as_data_uri(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->post('/api/auth/me/avatar', [
            'photo' => $this->pngUpload(),
        ])->assertOk()
            ->assertJsonPath('message', 'Profile photo updated.');

        $user->refresh();

        /*
         * Stored as a re-encoded square JPEG, never the original
         * bytes, and small enough for a plain BLOB column.
         */
        $this->assertSame('image/jpeg', $user->profile_photo_mime);

        $this->assertLessThan(60000, strlen($user->profile_photo));

        $decoded = imagecreatefromstring($user->profile_photo);

        $this->assertSame(ProfilePhotoService::SIZE, imagesx($decoded));

        $this->assertSame(ProfilePhotoService::SIZE, imagesy($decoded));

        /*
         * The photo travels inside /auth/me as a data URI and the
         * blob columns never leak through serialization.
         */
        $me = $this->getJson('/api/auth/me')->assertOk()->json();

        $this->assertStringStartsWith('data:image/jpeg;base64,', $me['avatar']);

        $this->assertArrayNotHasKey('profile_photo', $me);
    }

    public function test_non_images_are_rejected(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $path = tempnam(sys_get_temp_dir(), 'evil').'.jpg';

        file_put_contents($path, '<?php echo "not an image"; ?>');

        $this->post('/api/auth/me/avatar', [
            'photo' => new UploadedFile($path, 'evil.jpg', 'image/jpeg', null, true),
        ])->assertStatus(422);

        $this->assertNull($user->fresh()->profile_photo);
    }

    public function test_photo_can_be_removed(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->post('/api/auth/me/avatar', [
            'photo' => $this->pngUpload(200, 200),
        ])->assertOk();

        $this->deleteJson('/api/auth/me/avatar')
            ->assertOk()
            ->assertJsonPath('avatar', null);

        $this->assertNull($user->fresh()->profile_photo);
    }

    public function test_staff_email_stays_on_the_platform_domain(): void
    {
        $platform = Organisation::factory()->create([
            'name' => 'Kality Ltd',
            'is_platform' => true,
        ]);

        $admin = OrganisationContext::runAs(
            (int) $platform->id,
            fn (): User => User::factory()
                ->forOrganisation($platform)
                ->create([
                    'email' => 'staff@patrimoine365.com',
                    'role' => 'administrator',
                ])
        );

        Sanctum::actingAs($admin);

        /*
         * V1.0.48: the profile endpoint refuses ANY changed email — on
         * or off the domain — because the address moves only through
         * the verified three-step flow, where the domain rule is
         * enforced at initiation and again at completion.
         */
        $this->patchJson('/api/auth/me', [
            'given_names' => 'Staff',
            'surname' => 'Member',
            'email' => 'staff@gmail.test',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        $this->patchJson('/api/auth/me', [
            'given_names' => 'Staff',
            'surname' => 'Member',
            'email' => 'staff2@patrimoine365.com',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        /*
         * And the flow itself holds the domain: staff cannot even OPEN
         * a change that would leave it.
         */
        $this->postJson('/api/auth/email-change', [
            'email' => 'staff@gmail.test',
            'current_password' => 'password',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_customers_cannot_take_the_platform_domain(): void
    {
        $customer = User::factory()->create();

        Sanctum::actingAs($customer);

        $this->patchJson('/api/auth/me', [
            'given_names' => 'Sly',
            'surname' => 'Fox',
            'email' => 'sly@patrimoine365.com',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }
}
