<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SystemUserResolver
{
    public static function resolve(): User
    {
        $configuredId = (int) config('app.system_user_id', 1);
        $email = config('app.system_user_email', 'system@fitnesspro.app');
        $name = config('app.system_user_name', 'Fitness Pro System');

        $user = User::find($configuredId) ?? User::where('email', $email)->first();

        if (!$user) {
            $user = User::create([
                'name' => $name,
                'first_name' => 'Fitness',
                'last_name' => 'Assistant',
                'email' => $email,
                'password' => Hash::make(Str::random(40)),
                'email_verified_at' => now(),
            ]);
        } else {
            $user->updateQuietly([
                'name' => $name,
                'email' => $email,
            ]);
        }

        config(['app.system_user_id' => $user->id]);

        return $user;
    }

    public static function ids(): array
    {
        $ids = [];

        $configuredId = (int) config('app.system_user_id', 0);
        if ($configuredId > 0) {
            $ids[] = $configuredId;
        }

        $email = config('app.system_user_email');
        if ($email) {
            $emailId = User::where('email', $email)->value('id');
            if ($emailId) {
                $ids[] = (int) $emailId;
            }
        }

        $ids = array_values(array_unique(array_filter($ids)));

        if (empty($ids)) {
            $ids[] = self::resolve()->id;
        } else {
            config(['app.system_user_id' => $ids[0]]);
        }

        return $ids;
    }

    public static function id(): int
    {
        return self::ids()[0];
    }
}

