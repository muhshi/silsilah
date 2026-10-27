<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        $referredById = null;
        $refCode = $input['ref'] ?? $input['referral_code'] ?? session('referral_code');
        if (! empty($refCode)) {
            $referrer = User::where('referral_code', strtoupper(trim((string) $refCode)))->first();
            if ($referrer) {
                $referredById = $referrer->id;
            }
        }

        return User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
            'referred_by_id' => $referredById,
        ]);
    }
}
