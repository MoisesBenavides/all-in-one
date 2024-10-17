/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    '../src/views/*/*.html.twig',    // Ruta a tus archivos HTML (si los tienes)
  ],
  theme: {
    extend: {
      colors: {
        boton: '#dc2626',
        botonHover: '#b91c1c'
      },
    },
  },
  plugins: [],
}
