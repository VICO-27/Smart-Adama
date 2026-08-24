/** @type {import('tailwindcss').Config} */
export default {
  darkMode: 'class',
  content: [
    "./index.html",
    "./src/**/*.{vue,js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        brand: {
          50: '#F0F3FA',
          100: '#D5DEEF',
          200: '#B1C9EF',
          300: '#8AAEE0',
          400: '#638ECB',
          500: '#395886',
        },
        'smart-blue': {
          50: '#F0F3FA',
          100: '#D5DEEF',
          200: '#B1C9EF',
          300: '#8AAEE0',
          400: '#638ECB',
          500: '#395886',
          600: '#2A456C',
          700: '#1D3252',
          800: '#13223A',
          900: '#0B1526',
        }
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', 'Avenir', 'Helvetica', 'Arial', 'sans-serif'],
        display: ['Outfit', 'Inter', 'system-ui', 'sans-serif'],
      },
      boxShadow: {
        'premium': '0 4px 20px -2px rgba(57, 88, 134, 0.05), 0 0 3px rgba(57, 88, 134, 0.05)',
        'premium-hover': '0 8px 30px -4px rgba(57, 88, 134, 0.1), 0 0 4px rgba(57, 88, 134, 0.05)',
      }
    },
  },
  plugins: [],
}