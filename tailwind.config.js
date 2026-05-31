import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                rarity: {
                    common: '#94a3b8',
                    uncommon: '#10b981',
                    rare: '#0ea5e9',
                    epic: '#a855f7',
                    legendary: '#f59e0b',
                },
                quest: {
                    boss: '#ef4444',
                    main: '#fbbf24',
                    side: '#60a5fa',
                    daily: '#34d399',
                    learning: '#a855f7',
                    default: '#64748b',
                },
            },
            boxShadow: {
                'glow-uncommon': '0 0 24px rgba(16, 185, 129, 0.10)',
                'glow-rare': '0 0 24px rgba(56, 189, 248, 0.10)',
                'glow-epic': '0 0 24px rgba(168, 85, 247, 0.12)',
                'glow-legendary': '0 0 28px rgba(245, 158, 11, 0.14)',
                'glow-boss': '0 0 15px rgba(239, 68, 68, 0.15)',
                'glow-main': '0 0 15px rgba(234, 179, 8, 0.15)',
                'glow-learning': '0 0 10px rgba(168, 85, 247, 0.1)',
                'glow-in-progress': '0 0 10px rgba(99, 102, 241, 0.2)',
            },
        },
    },

    plugins: [forms],
};
