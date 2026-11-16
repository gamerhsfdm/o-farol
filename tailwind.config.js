/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./*.php",
    "./admin/*.php",
    "./includes/*.php",
    "./pages/*.php",
    "./assets/js/**/*.js"
  ],
  theme: {
    extend: {
      colors: {
        'farol-canvas': '#f5f3ec', // Bege clássico de papel jornal
        'farol-card': '#ffffff',
        'farol-border': '#e2ded5',
        'farol-red': '#b91c1c',    // Vermelho editorial das tags e botões
        'farol-red-hover': '#991b1b',
        'farol-teal': '#164e63',   // Azul petróleo para links e destaques
        'farol-teal-dark': '#0e3a4b',
        'farol-footer': '#eef2f6',
        'farol-dark': '#111827',
        'farol-gray': '#4b5563'
      },
      fontFamily: {
        'serif': ['"Playfair Display"', 'Georgia', 'Cambria', 'serif'],
        'sans': ['"Inter"', 'system-ui', '-apple-system', 'sans-serif']
      }
    },
  },
  plugins: [],
}
