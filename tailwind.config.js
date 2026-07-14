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
        espresso: '#16100b',
        roast: '#241a12',
        bean: '#3a2a1a',
        crema: '#ede4d3',
        foam: '#9c8b74',
        caramel: '#c49b63',
      },
      fontFamily: {
        sans: ['Poppins', 'sans-serif'],
      },
    },
  },
};
