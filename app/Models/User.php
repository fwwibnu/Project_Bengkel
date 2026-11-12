<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser, HasName
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',   // password otomatis di-hash, tidak pernah tersimpan plaintext
            'role' => Role::class,    // kolom role dibaca sebagai enum Role
        ];
    }

    /**
     * Semua peran adalah staf, jadi boleh masuk panel.
     * Pembatasan menu dan data per peran dilakukan di Policy dan tiap halaman.
     * Akun yang sudah dihapus (soft delete) otomatis tidak bisa login.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->role instanceof Role;
    }

    public function getFilamentName(): string
    {
        return $this->name;
    }

    /** Profil mekanik yang terhubung ke akun ini (khusus role Mekanik). */
    public function mechanic(): HasOne
    {
        return $this->hasOne(Mechanic::class);
    }

    /**
     * Cek apakah user punya salah satu peran yang disebut.
     * Contoh: $user->hasRole(Role::Admin, Role::Pemilik)
     */
    public function hasRole(Role ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    public function isMekanik(): bool
    {
        return $this->role === Role::Mekanik;
    }

    public function isKasir(): bool
    {
        return $this->role === Role::Kasir;
    }

    public function isPemilik(): bool
    {
        return $this->role === Role::Pemilik;
    }
}
