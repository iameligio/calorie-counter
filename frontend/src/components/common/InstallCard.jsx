import { useState } from 'react';
import { Card, CardContent } from './Card';
import { Button } from './Button';
import { Download, Share, Check } from 'lucide-react';
import useInstallStore, { promptInstall } from '../../store/installStore';

/**
 * Offers to install the app to the home screen.
 *
 * Renders nothing unless there is something real to offer — already installed,
 * or a browser that can't install at all (desktop Firefox, in-app webviews),
 * and this stays out of the way entirely.
 */
export default function InstallCard() {
  const { deferredPrompt, installed, isIos } = useInstallStore();
  const [dismissedPrompt, setDismissedPrompt] = useState(false);

  const canPrompt = !!deferredPrompt;
  // iOS fires no event, so the only route is manual instructions.
  const showIosHelp = isIos && !installed;

  if (installed || (!canPrompt && !showIosHelp)) return null;

  const handleInstall = async () => {
    const outcome = await promptInstall();
    if (outcome === 'dismissed') setDismissedPrompt(true);
  };

  return (
    <Card className="border-0 shadow-xl shadow-emerald-500/5">
      <CardContent className="pt-6 flex items-start gap-4">
        <img src="/icon.svg" alt="" className="h-12 w-12 rounded-xl flex-shrink-0" />

        <div className="flex-1 min-w-0">
          <h3 className="font-bold text-gray-900">Install Calorie Tracker</h3>

          {showIosHelp ? (
            <p className="text-sm text-gray-500 mt-1">
              Tap the <Share className="inline h-4 w-4 mx-0.5 -mt-0.5" aria-label="Share" />
              Share button in Safari, then choose <strong>Add to Home Screen</strong>.
            </p>
          ) : (
            <>
              <p className="text-sm text-gray-500 mt-1">
                Add it to your home screen for a full-screen app that opens
                straight to today's log.
              </p>
              <Button onClick={handleInstall} className="mt-4 w-full sm:w-auto">
                <Download className="h-4 w-4 mr-2" />
                Install app
              </Button>
              {dismissedPrompt && (
                <p className="text-xs text-gray-400 mt-2 flex items-center gap-1">
                  <Check className="h-3 w-3" />
                  No problem — it'll still be here whenever you want it.
                </p>
              )}
            </>
          )}
        </div>
      </CardContent>
    </Card>
  );
}
