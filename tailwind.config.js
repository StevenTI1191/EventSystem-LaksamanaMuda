import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/**
 * Tema perusahaan "Laksamana Muda" — netral hangat + emas deep.
 * Strategi: remap skala warna bawaan Tailwind yang sudah dipakai di seluruh app
 *   - gray-*             -> netral hangat (paper/line/ink)
 *   - yellow-* & amber-* -> emas perusahaan
 * sehingga ratusan class `bg-yellow-500`, `text-gray-600`, dll otomatis
 * mengikuti brand tanpa mengubah tiap komponen.
 *
 * @type {import('tailwindcss').Config}
 */

// Netral hangat (menggantikan gray dingin bawaan)
const warm = {
    50:  '#F7F6F4', // paper (background)
    100: '#F0ECE3',
    200: '#E7E1D3', // line / border
    300: '#D5CCB8',
    400: '#B0A794',
    500: '#8A8272',
    600: '#5C574D', // muted text
    700: '#4A4234', // ink-3
    800: '#3A3428', // ink-2
    900: '#2A2620', // ink (heading/teks utama)
    950: '#1B1813',
};

// Emas perusahaan (menggantikan yellow/amber)
const gold = {
    50:  '#FBF8F0',
    100: '#F2E9D3', // gold-soft
    200: '#E8D7A9',
    300: '#DBC076',
    400: '#C8961F', // gold-2 (terang)
    500: '#A9791F', // gold (aksen utama)
    600: '#916316',
    700: '#7A560F', // gold-dim
    800: '#614309',
    900: '#4E3708',
    950: '#2D1F04',
};

export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.jsx',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                display: ['Plus Jakarta Sans', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                gray: warm,
                yellow: gold,
                amber: gold,
                // Alias semantik (opsional dipakai di komponen baru)
                gold,
                ink: warm[900],
                paper: warm[50],
                line: warm[200],
            },
        },
    },

    plugins: [forms],
};
