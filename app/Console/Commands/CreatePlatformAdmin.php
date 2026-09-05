<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\Organisation;
use App\Models\User;
use App\Support\OrganisationContext;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * Create a platform administrator for the Kality Ltd staff
 * organisation.
 *
 * This is the console's only bootstrap path: platform staff have no
 * public signup, and further staff are invited from inside the console
 * itself. The email address must belong to the platform domain.
 *
 * Credentials are entered interactively; the password is hidden and
 * never reaches shell history.
 */
class CreatePlatformAdmin extends Command
{
    /**
     * @var string
     */
    protected $signature = 'patrimoine:create-platform-admin';

    /**
     * @var string
     */
    protected $description =
        'Create a Kality Ltd platform administrator securely';

    /**
     * Execute the command.
     */
    public function handle(): int
    {
        $this->newLine();

        $this->info('Create Patrimoine 365 platform administrator');

        $this->line(
            'The account must use an @'
            .User::PLATFORM_EMAIL_DOMAIN
            .' email address.'
        );

        $this->newLine();

        $name = trim((string) $this->ask('Full name'));

        $email = mb_strtolower(
            trim((string) $this->ask('Email address'))
        );

        $password = (string) $this->secret('Password');

        $passwordConfirmation =
            (string) $this->secret('Confirm password');

        $validator = Validator::make(
            [
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'password_confirmation' => $passwordConfirmation,
            ],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => [
                    'required',
                    'email',
                    'max:255',
                    'unique:users,email',
                    function (string $attribute, mixed $value, \Closure $fail): void {
                        if (
                            ! str_ends_with(
                                (string) $value,
                                '@'.User::PLATFORM_EMAIL_DOMAIN
                            )
                        ) {
                            $fail(
                                'Platform administrators must use an @'
                                .User::PLATFORM_EMAIL_DOMAIN
                                .' address.'
                            );
                        }
                    },
                ],
                'password' => [
                    'required',
                    'confirmed',
                    Password::defaults(),
                ],
            ]
        );

        if ($validator->fails()) {
            $this->newLine();

            $this->error('The platform administrator was not created:');

            foreach ($validator->errors()->all() as $message) {
                $this->line(" - {$message}");
            }

            $this->newLine();

            return self::FAILURE;
        }

        /*
         * The internal staff organisation is created on first use and
         * reused afterwards. It never counts as a customer anywhere.
         */
        $organisation = Organisation::query()->firstOrCreate(
            ['is_platform' => true],
            [
                'name' => (string) config('legal.product.name'),
                'status' => 'active',
                'trial_ends_on' => null,
            ]
        );

        $user = OrganisationContext::runAs(
            (int) $organisation->id,
            function () use ($name, $email, $password): User {
                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => $password,
                    'role' => UserRole::Administrator,
                    'is_active' => true,
                ]);

                /*
                 * Created deliberately by a trusted operator on the
                 * server; the address is considered verified.
                 */
                $user->forceFill([
                    'email_verified_at' => now(),
                ])->save();

                return $user;
            }
        );

        $this->newLine();

        $this->info('Platform administrator created successfully.');

        $this->table(
            ['Field', 'Value'],
            [
                ['User ID', $user->id],
                ['Name', $user->name],
                ['Email', $user->email],
                ['Organisation', $organisation->name.' (platform)'],
            ]
        );

        $this->newLine();

        return self::SUCCESS;
    }
}
