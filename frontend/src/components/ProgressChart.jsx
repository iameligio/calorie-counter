import React from 'react';
import { 
  AreaChart, 
  Area, 
  XAxis, 
  YAxis, 
  CartesianGrid, 
  Tooltip, 
  ResponsiveContainer, 
  ReferenceLine 
} from 'recharts';

const ProgressChart = ({ data, period, setPeriod }) => {
  if (!data || data.length === 0) {
    return (
      <div className="flex items-center justify-center h-64 bg-white/5 backdrop-blur-md rounded-2xl border border-white/10">
        <p className="text-gray-400">Not enough data to show progress yet.</p>
      </div>
    );
  }

  const CustomTooltip = ({ active, payload, label }) => {
    if (active && payload && payload.length) {
      return (
        <div className="bg-gray-900/90 backdrop-blur-xl border border-white/20 p-4 rounded-xl shadow-2xl">
          <p className="text-xs text-gray-400 mb-1">{label}</p>
          <p className="text-lg font-bold text-white">
            {payload[0].value} <span className="text-xs font-normal text-gray-400">kcal</span>
          </p>
          <p className={`text-xs mt-1 ${payload[0].value > payload[0].payload.target ? 'text-red-400' : 'text-emerald-400'}`}>
            Target: {payload[0].payload.target}
          </p>
        </div>
      );
    }
    return null;
  };

  return (
    <div className="bg-white/5 backdrop-blur-md rounded-2xl border border-white/10 p-6">
      <div className="flex items-center justify-between mb-8">
        <div>
          <h3 className="text-xl font-bold text-white">Calorie Progress</h3>
          <p className="text-sm text-gray-400">Track your daily consumption vs target</p>
        </div>
        
        <div className="flex bg-black/20 p-1 rounded-lg">
          <button
            onClick={() => setPeriod('week')}
            className={`px-4 py-1.5 rounded-md text-sm font-medium transition-all ${
              period === 'week' 
                ? 'bg-blue-600 text-white shadow-lg' 
                : 'text-gray-400 hover:text-white'
            }`}
          >
            7 Days
          </button>
          <button
            onClick={() => setPeriod('month')}
            className={`px-4 py-1.5 rounded-md text-sm font-medium transition-all ${
              period === 'month' 
                ? 'bg-blue-600 text-white shadow-lg' 
                : 'text-gray-400 hover:text-white'
            }`}
          >
            30 Days
          </button>
        </div>
      </div>

      <div className="h-72 w-full">
        <ResponsiveContainer width="100%" height="100%">
          <AreaChart data={data} margin={{ top: 10, right: 10, left: -20, bottom: 0 }}>
            <defs>
              <linearGradient id="colorCalories" x1="0" y1="0" x2="0" y2="1">
                <stop offset="5%" stopColor="#3b82f6" stopOpacity={0.3}/>
                <stop offset="95%" stopColor="#3b82f6" stopOpacity={0}/>
              </linearGradient>
            </defs>
            <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#ffffff10" />
            <XAxis 
              dataKey="label" 
              axisLine={false} 
              tickLine={false} 
              tick={{ fill: '#9ca3af', fontSize: 12 }} 
              dy={10}
            />
            <YAxis 
              axisLine={false} 
              tickLine={false} 
              tick={{ fill: '#9ca3af', fontSize: 12 }} 
            />
            <Tooltip content={<CustomTooltip />} cursor={{ stroke: '#3b82f6', strokeWidth: 2 }} />
            <ReferenceLine 
              y={data[0]?.target || 2000} 
              stroke="#ef4444" 
              strokeDasharray="3 3" 
              label={{ position: 'right', value: 'Target', fill: '#ef4444', fontSize: 10 }} 
            />
            <Area 
              type="monotone" 
              dataKey="total_calories" 
              stroke="#3b82f6" 
              strokeWidth={3}
              fillOpacity={1} 
              fill="url(#colorCalories)" 
              animationDuration={1500}
            />
          </AreaChart>
        </ResponsiveContainer>
      </div>
    </div>
  );
};

export default ProgressChart;
