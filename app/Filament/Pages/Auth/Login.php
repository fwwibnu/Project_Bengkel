<?php

namespace App\Filament\Pages\Auth;

use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Validation\ValidationException;

/**
 * Login pakai username (keputusan di kode). Email juga diterima supaya
 * sesuai dokumen PRD: kalau isian mengandung "@", dicocokkan ke kolom email.
 * Pembatasan percobaan login berulang tetap memakai bawaan Filament
 * (5 kali per menit, setelah itu harus menunggu).
 */
class Login extends BaseLogin
{
    public function getHeading(): string|Htmlable
    {
        return 'Masuk';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Gunakan akun yang dibuat oleh Admin bengkel.';
    }

    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        $this->getUsernameFormComponent(),
                        $this->getPasswordFormComponent(),
                        $this->getRememberFormComponent(),
                    ])
                    ->statePath('data'),
            ),
        ];
    }

    protected function getUsernameFormComponent(): Component
    {
        return TextInput::make('username')
            ->label('Username atau email')
            ->required()
            ->maxLength(255)
            ->autocomplete('username')
            ->autofocus()
            ->extraInputAttributes(['tabindex' => 1]);
    }

    /**
     * Data yang dipakai untuk mencocokkan akun.
     */
    protected function getCredentialsFromFormData(array $data): array
    {
        $login = trim((string) $data['username']);
        $field = str_contains($login, '@') ? 'email' : 'username';

        return [
            $field => $login,
            'password' => $data['password'],
        ];
    }

    /**
     * Pesan gagal sengaja tidak menyebut mana yang salah,
     * supaya orang lain tidak bisa menebak username yang terdaftar.
     */
    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.username' => 'Username atau password salah. Silakan coba lagi.',
        ]);
    }
}
