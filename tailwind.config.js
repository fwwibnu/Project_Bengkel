import forms from '@tailwindcss/forms';

/**
 * Tailwind untuk halaman Blade di luar panel: nota, laporan siap cetak, halaman error.
 * Warna & font disamakan dengan tema panel "Gading & Perunggu".
 *
 * @type {import('tailwindcss').Config}
 */
export default {
    content: [
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
            },
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
                ivory: '#F7F5F0',
            },
        },
    },
    plugins: [forms],
};
