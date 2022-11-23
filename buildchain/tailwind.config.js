module.exports = {
    content: [
        '../src/templates/**/*.{twig,html}',
        './src/**/*.{js,ts,vue,html}',
    ],
    theme: {
        // Extend the default Tailwind config here
        extend: {
        },
    },
    important: true,
    plugins: [
        require('@tailwindcss/typography'),
        require('@tailwindcss/forms'),
        require('@tailwindcss/line-clamp'),
        require('@tailwindcss/aspect-ratio'),
    ],
};
