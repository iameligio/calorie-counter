import React, { useEffect, useState } from 'react';
import Navbar from '../components/layout/Navbar';
import useDashboardStore from '../store/dashboardStore';
import { Card, CardContent, CardHeader, CardTitle } from '../components/common/Card';
import { Button } from '../components/common/Button';
import { Trash2, Flame, ChevronLeft, ChevronRight, Calendar, History as HistoryIcon } from 'lucide-react';
import axiosClient from '../services/axiosClient';

function ProgressBar({ current, target }) {
  const percentage = Math.min(100, Math.round((current / target) * 100)) || 0;
  const isOver = current > target;
  
  return (
    <div className="w-full">
      <div className="flex justify-between text-sm font-medium mb-2">
        <span className="text-gray-600">Daily Progress</span>
        <span className={isOver ? "text-red-500 font-bold" : "text-emerald-600"}>
          {current} / {target} kcal
        </span>
      </div>
      <div className="h-4 w-full bg-gray-200 rounded-full overflow-hidden shadow-inner font-bold">
        <div 
          className={`h-full rounded-full transition-all duration-1000 ease-out ${isOver ? 'bg-red-500' : 'bg-gradient-to-r from-emerald-400 to-teal-500'}`}
          style={{ width: `${percentage}%` }}
        />
      </div>
    </div>
  );
}

export default function History() {
  const { dashboard, fetchDashboard, isLoading } = useDashboardStore();
  const [logs, setLogs] = useState([]);
  const [summary, setSummary] = useState({ total_calories: 0, total_target: 2000, days_count: 1 });
  const [startDate, setStartDate] = useState(new Date().toISOString().split('T')[0]);
  const [endDate, setEndDate] = useState(new Date().toISOString().split('T')[0]);

  const refreshData = async () => {
    try {
      const { data } = await axiosClient.get('/logs', { 
        params: { 
          start_date: startDate,
          end_date: endDate
        } 
      });
      setLogs(data.logs || []);
      setSummary(data.summary || { total_calories: 0, total_target: 2000, days_count: 1 });
    } catch (e) {
      console.error(e);
      setLogs([]);
    }
  };

  useEffect(() => {
    refreshData();
  }, [startDate, endDate]);

  const setRangePreset = (type) => {
      const today = new Date();
      let start = new Date();
      let end = new Date();

      if (type === 'today') {
        // Already set
      } else if (type === 'week') {
        start.setDate(today.getDate() - 7);
      } else if (type === 'month') {
        start.setDate(1); // Start of month
      }

      setStartDate(start.toISOString().split('T')[0]);
      setEndDate(end.toISOString().split('T')[0]);
  };

  const changeRange = (days) => {
    const s = new Date(startDate);
    const e = new Date(endDate);
    s.setDate(s.getDate() + days);
    e.setDate(e.getDate() + days);
    
    const today = new Date().toISOString().split('T')[0];
    if (e.toISOString().split('T')[0] <= today) {
        setStartDate(s.toISOString().split('T')[0]);
        setEndDate(e.toISOString().split('T')[0]);
    }
  };

  const deleteLog = async (id) => {
    try {
      await axiosClient.delete(`/logs/${id}`);
      refreshData();
    } catch (err) {
      console.error(err);
    }
  };

  const isToday = startDate === endDate && startDate === new Date().toISOString().split('T')[0];
  const isRange = startDate !== endDate;

  return (
    <div className="min-h-screen bg-gray-50 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] pb-20">
      <Navbar />
      
      <main className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 mt-8 space-y-8">
        
        {/* Date Navigator Header */}
        <div className="flex flex-col sm:flex-row items-center justify-between bg-white px-6 py-4 rounded-2xl shadow-sm border border-gray-100 gap-4">
          <div className="flex items-center gap-4">
            <div className="p-2 bg-emerald-50 rounded-lg text-emerald-600">
               <HistoryIcon className="h-6 w-6" />
            </div>
            <div>
               <h2 className="text-2xl font-black text-gray-900 leading-tight">Food logs Archive</h2>
               <p className="text-xs text-gray-400 font-medium">Review your past consumption</p>
            </div>
          </div>

          <div className="flex flex-col sm:flex-row items-center gap-4">
            <div className="flex items-center bg-gray-50 rounded-lg p-1 border border-gray-100 mr-2">
                <button 
                  onClick={() => setRangePreset('today')}
                  className={`px-3 py-1 text-xs font-bold rounded-md transition-all ${isToday ? 'bg-white shadow-sm text-emerald-600' : 'text-gray-500 hover:text-gray-700'}`}
                >Today</button>
                <button 
                  onClick={() => setRangePreset('week')}
                  className={`px-3 py-1 text-xs font-bold rounded-md transition-all ${(!isToday && startDate === new Date(new Date().setDate(new Date().getDate() - 7)).toISOString().split('T')[0]) ? 'bg-white shadow-sm text-emerald-600' : 'text-gray-500 hover:text-gray-700'}`}
                >Last 7d</button>
                <button 
                  onClick={() => setRangePreset('month')}
                  className={`px-3 py-1 text-xs font-bold rounded-md transition-all ${(!isToday && startDate.endsWith('-01') && endDate === new Date().toISOString().split('T')[0]) ? 'bg-white shadow-sm text-emerald-600' : 'text-gray-500 hover:text-gray-700'}`}
                >This Month</button>
            </div>

            <div className="flex items-center bg-gray-50 rounded-xl p-1.5 border border-gray-100 shadow-inner">
              <button 
                onClick={() => changeRange(-1)}
                className="p-2 hover:bg-white hover:shadow-sm rounded-lg transition-all text-gray-600 cursor-pointer"
              >
                <ChevronLeft className="h-5 w-5" />
              </button>
              <div className="px-4 flex items-center gap-2">
                <input 
                  type="date"
                  max={endDate}
                  value={startDate}
                  onChange={(e) => setStartDate(e.target.value)}
                  className="bg-transparent text-sm font-bold text-gray-800 outline-none w-32 cursor-pointer"
                />
                <span className="text-gray-400 font-bold">→</span>
                <input 
                  type="date"
                  min={startDate}
                  max={new Date().toISOString().split('T')[0]}
                  value={endDate}
                  onChange={(e) => setEndDate(e.target.value)}
                  className="bg-transparent text-sm font-bold text-gray-800 outline-none w-32 cursor-pointer"
                />
              </div>
              <button 
                onClick={() => changeRange(1)}
                disabled={endDate >= new Date().toISOString().split('T')[0]}
                className={`p-2 rounded-lg transition-all ${endDate >= new Date().toISOString().split('T')[0] ? 'opacity-20 cursor-not-allowed' : 'hover:bg-white hover:shadow-sm text-gray-600 cursor-pointer'}`}
              >
                <ChevronRight className="h-5 w-5" />
              </button>
            </div>
          </div>
        </div>

        {/* History Summary Card */}
        <Card className="bg-white/90 border-0 shadow-xl shadow-gray-200/50">
          <CardHeader>
            <CardTitle className="text-gray-800 flex items-center gap-2">
              <HistoryIcon className="h-5 w-5 text-emerald-500" />
              Summary for {isToday ? "Today" : (isRange ? `${startDate} → ${endDate}` : startDate)}
            </CardTitle>
          </CardHeader>
          <CardContent>
              <div className="space-y-6">
                <div className="grid grid-cols-3 gap-8 text-center">
                  <div className="p-4 bg-gray-50 rounded-2xl">
                    <p className="text-xs text-gray-500 font-bold uppercase tracking-wider mb-1">
                        {isRange ? `Goal (${summary.days_count}d)` : "Goal"}
                    </p>
                    <p className="text-3xl font-black text-gray-900">{summary.total_target}</p>
                  </div>
                  <div className="p-4 bg-emerald-50 rounded-2xl">
                    <p className="text-xs text-emerald-600 font-bold uppercase tracking-wider mb-1">Total Consumed</p>
                    <p className="text-3xl font-black text-emerald-700">{summary.total_calories}</p>
                  </div>
                  <div className="p-4 bg-orange-50 rounded-2xl">
                    <p className="text-xs text-orange-600 font-bold uppercase tracking-wider mb-1">
                        {summary.total_target - summary.total_calories >= 0 ? "Under Goal" : "Over Goal"}
                    </p>
                    <p className="text-3xl font-black text-orange-700">
                        {Math.abs(summary.total_target - summary.total_calories)}
                    </p>
                  </div>
                </div>
                <ProgressBar current={summary.total_calories} target={summary.total_target} />
              </div>
          </CardContent>
        </Card>

        {/* Logs List */}
        <div className="space-y-4">
          <div className="flex items-center justify-between px-1">
            <h3 className="text-xl font-bold text-gray-900">
                Log Details
            </h3>
            <span className="text-xs font-bold text-gray-400">{logs.length} entries recorded</span>
          </div>
          
          <Card className="border-0 shadow-sm">
            <CardContent className="p-0">
              {isLoading ? (
                  <div className="p-12 flex justify-center">
                      <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-emerald-500"></div>
                  </div>
              ) : logs.length === 0 ? (
                <div className="py-16 text-center">
                    <div className="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-50 mb-4">
                         <HistoryIcon className="h-8 w-8 text-gray-300" />
                    </div>
                    <p className="text-gray-400 font-medium tracking-tight">No activity recorded for this date.</p>
                </div>
              ) : (
                <div className="divide-y divide-gray-50">
                  {Object.entries(logs.reduce((acc, log) => {
                      const date = new Date(log.consumed_at).toLocaleDateString([], { weekday: 'short', month: 'short', day: 'numeric' });
                      if (!acc[date]) acc[date] = [];
                      acc[date].push(log);
                      return acc;
                  }, {})).map(([date, dayLogs]) => (
                      <div key={date}>
                          <div className="bg-gray-50/50 px-5 py-2 text-[10px] font-black text-gray-400 uppercase tracking-widest border-y border-gray-100">
                              {date}
                          </div>
                          {dayLogs.map(log => (
                            <div key={log.id} className="flex items-center justify-between p-5 hover:bg-gray-50 group transition-colors">
                            <div className="flex items-center gap-4">
                              <div className="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600 font-bold text-sm shadow-sm border border-emerald-100">
                                  {log.food.name[0].toUpperCase()}
                              </div>
                              <div>
                                <p className="font-bold text-gray-800">{log.food.name}</p>
                                <p className="text-xs text-gray-400 font-medium">
                                  {log.grams}g &bull; {new Date(log.consumed_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}
                                </p>
                              </div>
                            </div>
                            <div className="flex items-center gap-6">
                              <span className="font-black text-emerald-600 text-lg">{log.calories} kcal</span>
                              {new Date(log.consumed_at) > new Date(Date.now() - 7 * 24 * 60 * 60 * 1000) && (
                                <button 
                                  onClick={() => deleteLog(log.id)}
                                  className="p-2 text-gray-300 hover:text-red-500 hover:bg-red-50 rounded-xl opacity-0 group-hover:opacity-100 transition-all cursor-pointer"
                                >
                                  <Trash2 className="h-4 w-4" />
                                </button>
                              )}
                            </div>
                          </div>
                          ))}
                      </div>
                  ))}
                </div>
              )}
            </CardContent>
          </Card>
        </div>
        
      </main>
    </div>
  );
}
