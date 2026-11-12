<?php

namespace App\Filament\Support;

use App\Exceptions\BusinessRuleException;
use Closure;
use Filament\Actions\MountableAction;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Menjalankan aksi Service dari UI Filament dan menampilkan toast:
 * - berhasil → toast hijau (kalau $successTitle diisi)
 * - aturan bisnis dilanggar → toast merah berisi pesan dari Service
 * - tidak punya akses → toast merah
 * Kalau $action diisi dan aksi gagal, modal tetap terbuka (halt) supaya isian tidak hilang.
 */
class ServiceAction
{
    /**
     * @return mixed hasil callback (atau true kalau callback tidak mengembalikan apa pun), null kalau gagal
     */
    public static function run(
        Closure $callback,
        ?string $successTitle = null,
        ?string $successBody = null,
        ?MountableAction $action = null,
    ): mixed {
        try {
            $result = $callback();
        } catch (BusinessRuleException $e) {
            static::fail('Gagal', $e->getMessage(), $action);

            return null;
        } catch (AuthorizationException) {
            static::fail('Akses ditolak', 'Kamu tidak punya akses untuk aksi ini.', $action);

            return null;
        }

        if ($successTitle) {
            Notification::make()
                ->success()
                ->title($successTitle)
                ->body($successBody)
                ->send();
        }

        return $result ?? true;
    }

    /** Dipakai di halaman Create/Edit: tampilkan toast lalu hentikan penyimpanan. */
    public static function haltWith(string $message, string $title = 'Gagal menyimpan'): never
    {
        Notification::make()->danger()->title($title)->body($message)->persistent()->send();

        throw new Halt();
    }

    protected static function fail(string $title, string $message, ?MountableAction $action): void
    {
        Notification::make()
            ->danger()
            ->title($title)
            ->body($message)
            ->send();

        $action?->halt();
    }
}
