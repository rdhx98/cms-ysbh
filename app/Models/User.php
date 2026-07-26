<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['name', 'handle', 'email', 'active', 'job_title', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable, LogsActivity;
    use HasRoles;
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email']) 
            ->logOnlyDirty()
            ->useLogName('user_updates'); 
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }
    public function handle(): string
    {
        return Str::of($this->handle);
    }
    public function getRouteKeyName()
    {
        return 'handle';
    }
    public function getRoleColor(string $roleName)
    {
        return match ($roleName) {
            'admin'  => 'bg-violet-50 text-violet-700 border-violet-700 dark:bg-violet-950 dark:text-violet-300',
            'editor' => 'bg-yellow-50 text-yellow-700 border-yellow-700 dark:bg-yellow-950 dark:text-yellow-300',
            // 'writer' => 'bg-sky-50 text-sky-700 border-sky-700 dark:bg-sky-950 dark:text-sky-300',
            'writer' => 'bg-sky-50 text-sky-700 border-sky-700 dark:bg-sky-950 dark:text-sky-300',
            default  => 'bg-zinc-50 text-zinc-700 border-zinc-700 dark:bg-zinc-950 dark:text-zinc-300',
        };
    }
    public function getRoleIcon(string $roleName)
    {
        return match ($roleName) {
            'admin'  => 'lucide-crown',
            'editor' => 'lucide-glasses',
            'writer' => 'lucide-feather',
            default  => 'lucide-user', // Ikon default jika role tidak dikenali
        };
    }
}
