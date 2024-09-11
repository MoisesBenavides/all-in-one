/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    '../app/views/*/*.html',    // Ruta a tus archivos HTML (si los tienes)
  ],
  theme: {
    extend: {
      colors: {
        primary: '#1DA1F2',   // Un ejemplo de color personalizado
        secondary: '#14171A',
      },
    },
  },
  plugins: [],
}
module.exports = {
  content: ['../app/views/*/*.html'],
  media: false,
  theme: {
    extend: {},
  },
  variants: {
    extend: {},
  },
  plugins: [],
};