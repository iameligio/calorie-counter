import { useEffect } from 'react';
import { WifiOff } from 'lucide-react';
import useConnectionStore from '../../store/connectionStore';

/**
 * Tells the user why the screen is empty when they open the installed app
 * without a connection.
 *
 * The service worker precaches the app shell, so offline the UI still boots —
 * it just can't reach the API. Without this banner that reads as a broken app
 * rather than a missing network.
 *
 * State lives in connectionStore, which axiosClient updates from real request
 * outcomes. The browser's own online/offline events are wired up here too, but
 * only as a fast hint — they fire on interface changes and miss the cases that
 * matter most (captive portals, dead uplinks).
 */
export default function OfflineBanner() {
  const offline = useConnectionStore((s) => s.offline);
  const reportUnreachable = useConnectionStore((s) => s.reportUnreachable);
  const reportReachable = useConnectionStore((s) => s.reportReachable);

  useEffect(() => {
    window.addEventListener('offline', reportUnreachable);
    window.addEventListener('online', reportReachable);
    return () => {
      window.removeEventListener('offline', reportUnreachable);
      window.removeEventListener('online', reportReachable);
    };
  }, [reportUnreachable, reportReachable]);

  if (!offline) return null;

  return (
    <div
      role="status"
      aria-live="polite"
      className="fixed inset-x-0 bottom-0 z-50 bg-amber-500 text-white shadow-lg"
      // Clears the iOS home indicator when running standalone.
      style={{ paddingBottom: 'env(safe-area-inset-bottom, 0px)' }}
    >
      <div className="max-w-5xl mx-auto flex items-center gap-2 px-4 py-3">
        <WifiOff className="h-4 w-4 flex-shrink-0" />
        <p className="text-sm font-medium">
          You're offline — showing what's already loaded. New logs won't save until you reconnect.
        </p>
      </div>
    </div>
  );
}
