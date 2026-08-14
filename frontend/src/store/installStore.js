import { create } from 'zustand';

/** The app is already installed and running from the home screen. */
function isStandalone() {
  return (
    window.matchMedia?.('(display-mode: standalone)').matches ||
    // iOS never adopted display-mode; it has its own flag.
    window.navigator.standalone === true
  );
}

/**
 * iOS and iPadOS, which fire no install event at all — the user has to go
 * through Share → Add to Home Screen by hand, so the UI has to say so.
 *
 * iPadOS 13+ reports itself as "Macintosh", so the user agent alone isn't
 * enough; a Mac with a touchscreen is the tell that it isn't really one.
 */
function isIosLike() {
  const ua = window.navigator.userAgent;
  if (/iPhone|iPad|iPod/i.test(ua)) return true;
  return navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1;
}

// Every mutation happens from the boot-time listeners below via setState, so
// the creator takes no setter of its own.
const useInstallStore = create(() => ({
  deferredPrompt: null,
  installed: isStandalone(),
  isIos: isIosLike(),
}));

/**
 * Starts listening for installability. Must be called at app boot, not from a
 * component: Chrome fires `beforeinstallprompt` once, during initial load. A
 * listener attached later — when the user eventually opens Settings — misses
 * it entirely, and the install button would never appear until a reload.
 */
export function watchInstallability() {
  window.addEventListener('beforeinstallprompt', (event) => {
    // Suppressing Chrome's own heuristic banner is the price of being able to
    // call prompt() on our terms. Whatever UI we render is now the only way in,
    // so it has to stay reachable.
    event.preventDefault();
    useInstallStore.setState({ deferredPrompt: event });
  });

  window.addEventListener('appinstalled', () => {
    useInstallStore.setState({ installed: true, deferredPrompt: null });
  });
}

/**
 * Opens the browser's install dialog. Resolves to Chrome's outcome
 * ('accepted' | 'dismissed'), or null if there was nothing to prompt with.
 */
export async function promptInstall() {
  const event = useInstallStore.getState().deferredPrompt;
  if (!event) return null;

  event.prompt();
  const { outcome } = await event.userChoice;

  // The event is single use. If the user dismissed but is still eligible,
  // Chrome fires a fresh one later and the listener above picks it up.
  useInstallStore.setState({ deferredPrompt: null });
  return outcome;
}

export default useInstallStore;
