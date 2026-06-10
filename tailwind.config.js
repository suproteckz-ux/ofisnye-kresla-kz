import typography from '@tailwindcss/typography'
import forms from '@tailwindcss/forms'
import aspectRatio from '@tailwindcss/aspect-ratio'

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
        './app/Filament/**/*.php',
    ],
    safelist: [
        'aspect-square',
        'line-clamp-2',
        'line-clamp-3',
        'hover:shadow-lg',
        'hover:shadow-md',
        'hover:border-amber-200',
        'hover:border-amber-300',
        'hover:bg-amber-50',
        'hover:text-amber-400',
        'hover:text-amber-700',
        'hover:scale-105',
        'group-hover:scale-105',
        'group-hover:grayscale-0',
        'group-hover:bg-amber-200',
        'hover:bg-green-400',
        'hover:bg-amber-400',
        'animate-pulse',
        'grayscale',
        'grayscale-0',
        'backdrop-blur',
        'blur-3xl',
        '-translate-x-1/2',
        '-translate-y-1/2',
    ],
    theme: {
        extend: {
            colors: {
                primary: {
                    50:  '#fffbeb',
                    100: '#fef3c7',
                    200: '#fde68a',
                    300: '#fcd34d',
                    400: '#fbbf24',
                    500: '#f59e0b',
                    600: '#d97706',
                    700: '#b45309',
                    800: '#92400e',
                    900: '#78350f',
                },
            },
            fontFamily: {
                sans: ['Inter', 'system-ui', 'sans-serif'],
            },
        },
    },
    plugins: [
        typography,
        forms,
        aspectRatio,
    ],
}
