/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    '../src/views/*/*.html.twig',    // Ruta a tus archivos HTML (si los tienes)
  ],
  theme: {
    extend: {
      colors: {
        botonAIO: {
          DEFAULT: '#dc2626',
          hover: '#b91c1c',
        },
        botonAIOParking: {
          DEFAULT: '#1f3de0',
          hover: '#233181',
        }
      },
    },
  },
  plugins: [],
  safelist: [
    'bg-botonAIO',
    'hover:bg-botonAIO-hover'
  ],

}
