// Sample Tailwind CSS configuration file
// Use with: https://www.jsdelivr.com/package/npm/tailwindcss

module.exports = {
  content: [
    './public/**/*.php',
    './includes/**/*.php',
    './admin/**/*.php',
  ],
  theme: {
    extend: {
      colors: {
        navy: {
          800: '#1e293b',
          900: '#0f172a',
          950: '#020617'
        },
        gold: {
          400: '#FFC107',
          500: '#FFA000',
          600: '#FF8F00'
        },
        green: {
          400: '#7CB342',
          500: '#66BB6A',
          600: '#4CAF50'
        },
        cms: {
          bg: '#0d1117',
          sidebar: '#161b22',
          card: '#21262d',
          border: '#30363d',
          hover: '#2d333b',
          accent: '#238636',
          accentHov: '#2ea043'
        },
      },
      fontFamily: {
        sans: ['Open Sans', 'sans-serif'],
        serif: ['Merriweather', 'serif'],
      },
    },
  },
  darkMode: 'class',
  plugins: [],
}
