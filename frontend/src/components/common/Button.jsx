import React from 'react';
import { clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';
import { Loader2 } from 'lucide-react';

export function cn(...inputs) {
  return twMerge(clsx(inputs));
}

export function Button({ 
  className, 
  variant = 'primary', 
  size = 'default', 
  isLoading, 
  children, 
  ...props 
}) {
  const baseStyles = 'inline-flex items-center justify-center rounded-xl font-medium transition-all duration-200 active:scale-95 disabled:opacity-70 disabled:pointer-events-none ring-offset-2 focus-visible:outline-none focus-visible:ring-2';
  
  const variants = {
    primary: 'bg-gradient-to-r from-emerald-500 to-teal-600 text-white hover:from-emerald-600 hover:to-teal-700 shadow-md shadow-emerald-500/20 focus-visible:ring-emerald-500',
    secondary: 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-200 shadow-sm focus-visible:ring-gray-400',
    ghost: 'hover:bg-gray-100/80 text-gray-700 hover:text-gray-900 focus-visible:ring-gray-400',
    danger: 'bg-red-500 text-white hover:bg-red-600 shadow-sm focus-visible:ring-red-500'
  };

  const sizes = {
    default: 'h-11 px-4 py-2',
    sm: 'h-9 rounded-lg px-3 text-sm',
    lg: 'h-14 rounded-2xl px-8 text-lg',
    icon: 'h-11 w-11'
  };

  return (
    <button
      className={cn(baseStyles, variants[variant], sizes[size], className)}
      disabled={isLoading || props.disabled}
      {...props}
    >
      {isLoading && <Loader2 className="mr-2 h-5 w-5 animate-spin" />}
      {!isLoading && children}
    </button>
  );
}
