import { clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

/**
 * Merge Tailwind class names, resolving conflicts (later classes win).
 * Single source of truth used by every UI primitive.
 */
export function cn(...inputs) {
  return twMerge(clsx(inputs));
}
