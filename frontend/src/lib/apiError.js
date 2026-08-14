/**
 * Turns a rejected axios request into a message worth showing a user.
 *
 * The distinction that matters is whether the server answered at all. An axios
 * error with no `response` never reached it — dead uplink, captive portal, API
 * down. Falling through to a generic "Login failed, please try again" in that
 * case actively misleads: it reads as wrong credentials, so the user retypes a
 * correct password and fails again.
 *
 * @param {unknown} err       the caught axios rejection
 * @param {string}  fallback  used only when the server responded but gave no message
 */
export function apiErrorMessage(err, fallback) {
  if (!err?.response) {
    return "Can't reach the server. Check your connection and try again.";
  }

  // Laravel's 422 carries per-field detail; the password policy usually trips
  // more than one rule at a time, so surface all of them rather than the first.
  const fieldErrors = Object.values(err.response.data?.errors ?? {}).flat();
  if (fieldErrors.length) return fieldErrors.join(' ');

  return err.response.data?.message || fallback;
}
