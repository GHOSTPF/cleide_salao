import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './app/**/*.php',
    ],

    theme: {
        extend: {
            colors: {
                luna: {
                    cream: '#F4EEE6',
                    sand: '#E8DCCB',
                    nude: '#D9C2A7',
                    gold: '#B08D57',
                    brown: '#8A6A4F',
                    ink: '#2E2925',
                },
            },
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                serif: ['"Cormorant Garamond"', ...defaultTheme.fontFamily.serif],
            },
            letterSpacing: {
                luna: '0.25em',
            },
            boxShadow: {
                soft: '0 10px 40px -12px rgba(46, 41, 37, 0.12)',
            },
        },
    },

    plugins: [forms],
};
