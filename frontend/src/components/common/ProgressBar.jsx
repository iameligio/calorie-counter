import { cn } from '../../lib/utils';

/**
 * Horizontal calorie progress bar. Turns red once consumption exceeds target.
 */
export default function ProgressBar({ current, target, label = 'Progress' }) {
  const safeTarget = target > 0 ? target : 1;
  const percentage = Math.min(100, Math.round((current / safeTarget) * 100));
  const isOver = current > target;

  return (
    <div className="w-full">
      <div className="flex justify-between text-sm font-medium mb-2">
        <span className="text-gray-600">{label}</span>
        <span className={cn('text-emerald-600', isOver && 'text-red-500 font-bold')}>
          {current} / {target} kcal
        </span>
      </div>
      <div className="h-4 w-full bg-gray-100 rounded-full overflow-hidden shadow-inner">
        <div
          className={cn(
            'h-full rounded-full transition-all duration-1000 ease-out',
            isOver ? 'bg-red-500' : 'bg-gradient-to-r from-emerald-400 to-teal-500'
          )}
          style={{ width: `${percentage}%` }}
        />
      </div>
    </div>
  );
}
