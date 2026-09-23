import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            colors: {
                primary: {
                    DEFAULT: '#EF5A35',
                    dark: '#ad2b08',
                    container: '#d04421',
                    subtle: '#FFF1EE',
                },
                secondary: {
                    DEFAULT: '#006c49',
                    container: '#6cf8bb',
                },
                tertiary: {
                    DEFAULT: '#00647e',
                    container: '#007f9e',
                },
                background: '#F8F9FA',
                surface: {
                    DEFAULT: '#FFFFFF',
                    bright: '#f8f9ff',
                    container: '#e6eeff',
                    'container-low': '#eff4ff',
                    'container-high': '#dee9fc',
                },
                'text-primary': '#1F2937',
                'text-muted': '#6B7280',
                'border-subtle': '#E5E7EB',
                'error-alert': '#EF4444',
            },
            fontFamily: {
                sans: ['Plus Jakarta Sans', 'Inter', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
