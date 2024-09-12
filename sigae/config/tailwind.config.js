/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    '../app/views/*/*.html',    // Ruta a tus archivos HTML (si los tienes)
  ],
  theme: {
    extend: {
      colors: {
        neutro: '#d1cfcf',
        alternativo: '#162B9D',   // Un ejemplo de color personalizado
        acento: '#95092D',
        secundario: '#8B8989',
        blancoAlt: '#f7f6f8'
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