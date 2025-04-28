import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import flowbite from 'flowbite/plugin';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './node_modules/flowbite/**/*.js'
    ],
    safelist: [
        'bg-weekend-sat',
        'bg-weekend-sun',
    ],

    theme: {
        extend: {
            colors: {
                'weekend-sat': 'rgba(0, 0, 255, 0.1)',
                'weekend-sun': 'rgba(255, 0, 0, 0.1)',
            },
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms, flowbite],
};
