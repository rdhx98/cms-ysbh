<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Concerns\PasswordValidationRules;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

class ResetUserPassword implements ResetsUserPasswords
{
    use PasswordValidationRules;

    /**
     * Validate and reset the user's forgotten password.
     *
     * @param  array<string, string>  $input
     */
    public function reset(User $user, array $input): void
    {
        Validator::make($input, [
            'password' => $this->passwordRules(),
        ])->validate();

        $user->forceFill([
            'password' => $input['password'],
        ])->save();

        activity('security')
            ->performedOn($user)
            ->causedBy($user) 
            ->withProperties([
                'ip_address' => request()->ip(),
                'browser' => request()->userAgent(),
                'type' => 'password_reset' // Penanda bahwa ini hasil dari fitur lupa password
            ])
            ->log('Kata sandi berhasil direset (Lupa Password)');
    }
}
