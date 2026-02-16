import type { Config } from 'tailwindcss'

export default {
  darkMode: 'class',
  theme: {
    extend: {
      colors: {
        surface: {
          DEFAULT: '#09090b',
          50: '#18181b',
          100: '#1c1c20',
          200: '#27272a',
          300: '#3f3f46',
        },
        accent: {
          DEFAULT: '#a78bfa',
          dim: '#7c3aed',
        },
        muted: '#71717a',
      },
      fontFamily: {
        sans: ['"DM Sans"', 'system-ui', 'sans-serif'],
        mono: ['"JetBrains Mono"', 'monospace'],
      },
    },
  },
} satisfies Config
