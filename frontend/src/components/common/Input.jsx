import React, { forwardRef } from 'react';
import { clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs) {
  return twMerge(clsx(inputs));
}

export const Input = forwardRef(({ className, label, error, ...props }, ref) => {
  return (
    <div className="w-full">
      {label && (
        <label className="block text-sm font-medium text-gray-700 mb-1.5 ml-1">
          {label}
        </label>
      )}
      <input
        ref={ref}
        className={cn(
          "flex h-11 w-full rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm text-gray-900 transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-gray-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500/50 focus-visible:border-emerald-500 disabled:cursor-not-allowed disabled:opacity-50 shadow-sm",
          error && "border-red-500 focus-visible:ring-red-500/50 focus-visible:border-red-500",
          className
        )}
        {...props}
      />
      {error && (
        <p className="mt-1.5 text-sm text-red-500 ml-1 font-medium">{error}</p>
      )}
    </div>
  );
});
Input.displayName = 'Input';
