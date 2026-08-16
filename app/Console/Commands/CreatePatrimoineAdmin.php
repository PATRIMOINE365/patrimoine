<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * Create a Patrimoine Administrator account.
 *
 * Patrimoine deliberately does not seed default production credentials.
 * This command provides a controlled bootstrap mechanism for creating the
 * first application administrator, and may also be used later to create
 * another Property Manager when explicitly required.
 *
 * Password input is hidden from the terminal and is never written to logs,
 * source files or command history.
 */
class CreatePatrimoineAdmin extends Command
{
    /**
     * Command name used by administrators.
     *
     * No credentials are accepted as command-line options because doing so
     * could expose passwords through shell history or process listings.
     *
     * @var string
     */
    protected $signature = 'patrimoine:create-admin';

    /**
     * Human-readable command description.
     *
     * @var string
     */
    protected $description =
        'Create a Patrimoine Administrator account securely';

    /**
     * Execute the command.
     */
    public function handle(): int
    {
        $this->newLine();

        $this->info(
            'Create Patrimoine Administrator'
        );

        $this->line(
            'Credentials will be entered interactively and the password will remain hidden.'
        );

        $this->newLine();

        /*
         * -------------------------------------------------------------
         * Name
         * -------------------------------------------------------------
         */
        $name =
            trim(
                (string) $this->ask(
                    'Full name'
                )
            );

        /*
         * -------------------------------------------------------------
         * Email
         * -------------------------------------------------------------
         *
         * Normalize the email before validation and persistence so duplicate
         * accounts cannot be created merely by changing letter case.
         */
        $email =
            mb_strtolower(
                trim(
                    (string) $this->ask(
                        'Email address'
                    )
                )
            );

        /*
         * -------------------------------------------------------------
         * Password
         * -------------------------------------------------------------
         *
         * secret() prevents the password from being displayed while typed.
         *
         * Asking twice protects against accidental mistyping during initial
         * production bootstrap.
         */
        $password =
            (string) $this->secret(
                'Password'
            );

        $passwordConfirmation =
            (string) $this->secret(
                'Confirm password'
            );

        /*
         * Use Laravel validation rules rather than reproducing validation
         * logic manually.
         *
         * The password requirement is deliberately stronger than the basic
         * framework default because this account controls financial and
         * property-management information.
         */
        $validator =
            Validator::make(
                [
                    'name' =>
                        $name,

                    'email' =>
                        $email,

                    'password' =>
                        $password,

                    'password_confirmation' =>
                        $passwordConfirmation,
                ],
                [
                    'name' => [
                        'required',
                        'string',
                        'max:255',
                    ],

                    'email' => [
                        'required',
                        'email',
                        'max:255',
                        'unique:users,email',
                    ],

                    'password' => [
                        'required',
                        'confirmed',

                        Password::min(12)
                            ->letters()
                            ->mixedCase()
                            ->numbers()
                            ->symbols(),
                    ],
                ]
            );

        if ($validator->fails()) {
            $this->newLine();

            $this->error(
                'The administrator account could not be created.'
            );

            foreach (
                $validator->errors()->all()
                as $message
            ) {
                $this->line(
                    " - {$message}"
                );
            }

            $this->newLine();

            return self::FAILURE;
        }

        /*
         * The User model's password cast hashes the password automatically.
         *
         * We deliberately do not call Hash::make() here so password hashing
         * remains centralized in the User model.
         */
        $user =
            User::create([
                'name' =>
                    $name,

                'email' =>
                    $email,

                'password' =>
                    $password,

                /*
                 * This bootstrap command deliberately creates an
                 * Administrator account.
                 */
                'role' =>
                    UserRole::Administrator,

                /*
                 * Accounts are created deliberately by a trusted system
                 * administrator, therefore the email is considered verified
                 * at bootstrap time.
                 */
                'email_verified_at' =>
                    now(),
            ]);

        $this->newLine();

        $this->info(
            'Patrimoine administrator created successfully.'
        );

        $this->table(
            [
                'Field',
                'Value',
            ],
            [
                [
                    'User ID',
                    $user->id,
                ],
                [
                    'Name',
                    $user->name,
                ],
                [
                    'Email',
                    $user->email,
                ],
                [
                    'Role',
                    $user->role,
                ],
            ]
        );

        $this->newLine();

        return self::SUCCESS;
    }
}
