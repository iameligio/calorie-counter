import React from 'react'
import { createRoot } from 'react-dom/client'
import { BrowserRouter } from 'react-router-dom'
import { registerSW } from 'virtual:pwa-register'
import { watchInstallability } from './store/installStore'
import App from './App.jsx'
import './index.css'

// Before render: Chrome fires `beforeinstallprompt` during initial load, and
// the install button in Settings mounts far too late to catch it.
watchInstallability()

// `autoUpdate` in vite.config.js means a new service worker activates as soon
// as it is fetched; `immediate` starts that check on load instead of waiting
// for the next navigation. No update prompt to dismiss — for an app this size,
// silently running the latest build is the right trade.
//
// This is a no-op in `npm run dev`, where the plugin stubs the virtual module.
registerSW({ immediate: true })

createRoot(document.getElementById('root')).render(
  <React.StrictMode>
    <BrowserRouter>
      <App />
    </BrowserRouter>
  </React.StrictMode>,
)
