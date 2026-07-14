/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './public/**/*.php',
    './src/partials/**/*.php',
    './public/assets/chat.js',
  ],
  theme: {
    extend: {
      colors: {
        espresso: 'rgb(var(--c-espresso) / <alpha-value>)',
        roast: 'rgb(var(--c-roast) / <alpha-value>)',
        bean: 'rgb(var(--c-bean) / <alpha-value>)',
        crema: 'rgb(var(--c-crema) / <alpha-value>)',
        foam: 'rgb(var(--c-foam) / <alpha-value>)',
        caramel: 'rgb(var(--c-caramel) / <alpha-value>)',
      },
      fontFamily: {
        sans: ['Poppins', 'sans-serif'],
      },
    },
  },
};
