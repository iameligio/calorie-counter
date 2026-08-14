import { create } from 'zustand';

/**
 * Tracks whether the API is actually reachable.
 *
 * `navigator.onLine` only reports whether a network interface is up. It stays
 * true on a captive portal, on wifi with a dead uplink, and under Chrome's own
 * offline emulation — all cases where every request fails. So the browser
 * events are treated as a hint and the authoritative signal comes from
 * axiosClient, which reports whether requests are actually completing.
 */
const useConnectionStore = create((set) => ({
  offline: !navigator.onLine,

  // A request never reached the server (no HTTP response at all).
  reportUnreachable: () => set({ offline: true }),

  // A request completed — even a 4xx/5xx proves the connection is alive.
  reportReachable: () => set({ offline: false }),
}));

export default useConnectionStore;
