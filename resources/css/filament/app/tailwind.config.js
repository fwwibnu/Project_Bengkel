import preset from '../../../../vendor/filament/filament/tailwind.config.preset'

export default {
    presets: [preset],
    content: [
        './app/Filament/**/*.php',
        './resources/views/filament/**/*.blade.php',
        './resources/views/laporan/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
    ],
    theme: {
        extend: {
            // Warna perunggu yang sama dengan halaman cetak & error (tailwind.config.js di root)
            colors: {
                bronze: {
                    50: '#FAF5EE',
                    100: '#F3E8D9',
                    200: '#E6D0B4',
                    300: '#D6B28A',
                    400: '#BE8C5C',
                    500: '#A66E3C',
                    600: '#8C5A2B',
                    700: '#73481F',
                    800: '#5C3A1B',
                    900: '#422A14',
                    950: '#28190C',
                },
            },
        },
    },
}
