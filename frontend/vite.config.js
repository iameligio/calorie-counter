import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import tailwindcss from '@tailwindcss/vite'
import { VitePWA } from 'vite-plugin-pwa'

// https://vite.dev/config/
export default defineConfig({
  plugins: [
    react(),
    tailwindcss(),
    VitePWA({
      registerType: 'autoUpdate',

      // main.jsx registers via `virtual:pwa-register`, so the plugin must not
      // also inject its own registration script.
      injectRegister: null,

      // The service worker is deliberately off during `npm run dev`. A live SW
      // caching a dev build is a reliable way to spend an afternoon wondering
      // why an edit isn't showing up. Test the PWA with `build` + `preview`.
      devOptions: { enabled: false },

      // No `includeAssets` here on purpose — the globPatterns below already
      // sweep up every png/svg in public/, and listing them twice puts
      // duplicate entries in the precache manifest.

      manifest: {
        id: '/',
        name: 'Calorie Tracker',
        short_name: 'Calories',
        description: 'Log meals, track calories, and watch your progress over time.',
        start_url: '/',
        scope: '/',
        display: 'standalone',
        // theme_color paints the Android status bar in standalone mode. The
        // app's own navbar is white, so anything else leaves a coloured band
        // floating above a white header. Branding lives in the icon instead.
        theme_color: '#ffffff',
        background_color: '#ffffff',
        icons: [
          { src: '/pwa-192.png', sizes: '192x192', type: 'image/png', purpose: 'any' },
          { src: '/pwa-512.png', sizes: '512x512', type: 'image/png', purpose: 'any' },
          { src: '/maskable-512.png', sizes: '512x512', type: 'image/png', purpose: 'maskable' },
        ],

        // Long-press actions on the installed launcher icon (Android; ignored
        // by iOS). These land on guarded routes — the router bounces to /login
        // when there's no session, which is the correct behaviour.
        shortcuts: [
          {
            name: "Today's log",
            short_name: 'Today',
            description: "Jump straight to today's food log",
            url: '/dashboard',
            icons: [{ src: '/shortcut-dashboard.png', sizes: '96x96', type: 'image/png' }],
          },
          {
            name: 'History',
            short_name: 'History',
            description: 'Review previous days and their totals',
            url: '/history',
            icons: [{ src: '/shortcut-history.png', sizes: '96x96', type: 'image/png' }],
          },
          {
            name: 'Settings',
            short_name: 'Settings',
            description: 'Adjust your profile and calorie target',
            url: '/settings',
            icons: [{ src: '/shortcut-settings.png', sizes: '96x96', type: 'image/png' }],
          },
        ],
      },

      workbox: {
        globPatterns: ['**/*.{js,css,html,svg,png,ico,woff,woff2}'],

        // vite-plugin-pwa injects the manifest's own icons into the precache
        // manifest already — shortcut icons included. Without this they are
        // listed twice.
        globIgnores: ['pwa-*.png', 'maskable-*.png', 'shortcut-*.png'],

        // Recharts pushes the main chunk past Workbox's 2 MiB default.
        maximumFileSizeToCacheInBytes: 4 * 1024 * 1024,

        // Offline navigations boot the real app shell rather than a dead-end
        // error page. The app then reports its own connection state.
        navigateFallback: '/index.html',

        // Only matters if VITE_API_BASE_URL is ever pointed at a same-origin
        // path — a cross-origin API is never intercepted in the first place.
        // Cheap insurance against serving index.html in place of JSON.
        navigateFallbackDenylist: [/^\/api\//],

        // NOTE: there is deliberately no runtimeCaching for the API. Cached
        // calorie totals that silently disagree with the server are worse than
        // no data at all. Offline reads are a Tier 2 concern.
        cleanupOutdatedCaches: true,
        clientsClaim: true,
      },
    }),
  ],
  server: {
    host: '127.0.0.1',
    port: 5174,
    allowedHosts: ['front.myfitnesspal.test']
  }
})
