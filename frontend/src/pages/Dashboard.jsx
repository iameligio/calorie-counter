import React, { useEffect, useState } from 'react';
import Navbar from '../components/layout/Navbar';
import useDashboardStore from '../store/dashboardStore';
import useAuthStore from '../store/authStore';
import { Card, CardContent, CardHeader, CardTitle } from '../components/common/Card';
import { Button } from '../components/common/Button';
import { Input } from '../components/common/Input';
import { Search, Plus, Trash2, Flame, X, UtensilsCrossed, Award, Scale, Target } from 'lucide-react';
import axiosClient from '../services/axiosClient';
import ProgressChart from '../components/ProgressChart';

function ProgressBar({ current, target }) {
  const percentage = Math.min(100, Math.round((current / target) * 100)) || 0;
  const isOver = current > target;
  
  return (
    <div className="w-full">
      <div className="flex justify-between text-sm font-medium mb-2">
        <span className="text-gray-600">Progress</span>
        <span className={isOver ? "text-red-500 font-bold" : "text-emerald-600"}>
          {current} / {target} kcal
        </span>
      </div>
      <div className="h-4 w-full bg-gray-100 rounded-full overflow-hidden shadow-inner">
        <div 
          className={`h-full rounded-full transition-all duration-1000 ease-out ${isOver ? 'bg-red-500' : 'bg-gradient-to-r from-emerald-400 to-teal-500'}`}
          style={{ width: `${percentage}%` }}
        />
      </div>
    </div>
  );
}

function AddCustomFoodModal({ isOpen, onClose, onFoodCreated }) {
  const [name, setName] = useState('');
  const [caloriesPer100g, setCaloriesPer100g] = useState('');
  const [protein, setProtein] = useState('');
  const [carbs, setCarbs] = useState('');
  const [fat, setFat] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState('');

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setIsSubmitting(true);
    try {
      const { data } = await axiosClient.post('/foods', {
        name,
        calories_per_100g: parseInt(caloriesPer100g),
        protein: protein ? parseFloat(protein) : null,
        carbs: carbs ? parseFloat(carbs) : null,
        fat: fat ? parseFloat(fat) : null,
      });
      onFoodCreated(data);
      // Reset fields
      setName(''); setCaloriesPer100g(''); setProtein(''); setCarbs(''); setFat('');
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to create food.');
    } finally {
      setIsSubmitting(false);
    }
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
      <div className="bg-white rounded-2xl shadow-2xl w-full max-w-md relative animate-in fade-in">
        <div className="flex items-center justify-between p-5 border-b border-gray-100">
          <h3 className="text-lg font-bold text-gray-900 flex items-center gap-2">
            <UtensilsCrossed className="h-5 w-5 text-emerald-500" />
            Add Custom Food
          </h3>
          <button onClick={onClose} className="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors cursor-pointer">
            <X className="h-5 w-5" />
          </button>
        </div>
        <form onSubmit={handleSubmit} className="p-5 space-y-4">
          {error && <div className="p-3 bg-red-50 text-red-600 text-sm rounded-xl">{error}</div>}
          <p className="text-xs text-amber-600 bg-amber-50 p-3 rounded-xl">
            ⚠️ Custom foods are for personal use only and won't appear for other users.
          </p>
          <Input 
            label="Food Name" 
            required 
            value={name} 
            onChange={e => setName(e.target.value)} 
            placeholder="e.g. Homemade Adobo"
          />
          <Input 
            label="Calories per 100g" 
            type="number" 
            required 
            min="0" 
            value={caloriesPer100g} 
            onChange={e => setCaloriesPer100g(e.target.value)} 
            placeholder="e.g. 220"
          />
          <div className="grid grid-cols-3 gap-3">
            <Input 
              label="Protein (g)" 
              type="number" 
              min="0" 
              step="0.1" 
              value={protein} 
              onChange={e => setProtein(e.target.value)} 
              placeholder="0"
            />
            <Input 
              label="Carbs (g)" 
              type="number" 
              min="0" 
              step="0.1" 
              value={carbs} 
              onChange={e => setCarbs(e.target.value)} 
              placeholder="0"
            />
            <Input 
              label="Fat (g)" 
              type="number" 
              min="0" 
              step="0.1" 
              value={fat} 
              onChange={e => setFat(e.target.value)} 
              placeholder="0"
            />
          </div>
          <Button type="submit" className="w-full" isLoading={isSubmitting}>
            Save Custom Food
          </Button>
        </form>
      </div>
    </div>
  );
}

export default function Dashboard() {
  const { dashboard, fetchDashboard, isLoading } = useDashboardStore();
  const { user } = useAuthStore();
  
  const [logs, setLogs] = useState([]);
  const [search, setSearch] = useState('');
  const [searchResults, setSearchResults] = useState([]);
  const [isSearching, setIsSearching] = useState(false);
  const [hasSearched, setHasSearched] = useState(false);
  const [grams, setGrams] = useState({});
  const [showCustomModal, setShowCustomModal] = useState(false);
  
  // Progress state
  const [historyData, setHistoryData] = useState([]);
  const [period, setPeriod] = useState('week');
  const [streak, setStreak] = useState(0);

  const fetchProgress = async (p = period) => {
    try {
      const { data } = await axiosClient.get(`/progress?period=${p}`);
      setHistoryData(data.history);
      setStreak(data.streak);
    } catch (err) {
      console.error('Failed to fetch progress:', err);
    }
  };

  const refreshData = async () => {
    fetchDashboard();
    fetchProgress();
    try {
      const { data } = await axiosClient.get('/logs');
      // The API now returns {logs:[], summary:{}} instead of just []
      setLogs(Array.isArray(data) ? data : (data.logs || []));
    } catch (e) {
      console.error(e);
      setLogs([]);
    }
  };

  useEffect(() => {
    fetchProgress(period);
  }, [period]);

  useEffect(() => {
    refreshData();
  }, []);

  const handleSearch = async (e) => {
    e.preventDefault();
    if (!search.trim()) return;
    setIsSearching(true);
    setHasSearched(true);
    try {
      const { data } = await axiosClient.get(`/foods?search=${encodeURIComponent(search)}`);
      setSearchResults(data.data);
      const g = {};
      data.data.forEach(f => g[f.id] = 100);
      setGrams(prev => ({ ...prev, ...g }));
    } catch (err) {
      console.error(err);
    } finally {
      setIsSearching(false);
    }
  };

  const logFood = async (foodId) => {
    const amount = grams[foodId] || 100;
    try {
      await axiosClient.post('/logs', {
        food_id: foodId,
        grams: amount
      });
      refreshData();
    } catch (err) {
      console.error(err);
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

  const handleCustomFoodCreated = (food) => {
    // Add the newly created food to the search results so the user can log it immediately
    setSearchResults(prev => [food, ...prev]);
    setGrams(prev => ({ ...prev, [food.id]: 100 }));
    setShowCustomModal(false);
  };

  return (
    <div className="min-h-screen bg-gray-50 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] pb-20">
      <Navbar />
      
      <main className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 mt-8 space-y-8">
        
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div className="col-span-1 md:col-span-2">
            {/* Summary Card */}
            <Card className="bg-white/80 border-0 shadow-lg shadow-emerald-500/5 h-full">
          <CardHeader>
            <CardTitle className="text-gray-800 flex items-center">
              <Flame className="mr-2 h-5 w-5 text-orange-500" />
              Daily Summary for Today
            </CardTitle>
          </CardHeader>
          <CardContent>
            {dashboard ? (
              <div className="space-y-6">
                <div className="grid grid-cols-3 gap-4 text-center divide-x divide-gray-100">
                  <div>
                    <p className="text-sm text-gray-500 font-medium">Target</p>
                    <p className="text-2xl font-bold text-gray-900">{dashboard.calorie_target}</p>
                  </div>
                  <div>
                    <p className="text-sm text-gray-500 font-medium">Consumed</p>
                    <p className="text-2xl font-bold text-gray-900">{dashboard.total_calories}</p>
                  </div>
                  <div>
                    <p className="text-sm text-gray-500 font-medium">Remaining</p>
                    <p className={`text-2xl font-bold ${dashboard.remaining_calories === 0 ? 'text-red-500' : 'text-emerald-500'}`}>
                      {dashboard.remaining_calories}
                    </p>
                  </div>
                </div>
                <ProgressBar current={dashboard.total_calories} target={dashboard.calorie_target} />
                
                {/* Streak Badge */}
                {streak > 0 && (
                  <div className="flex items-center gap-2 bg-orange-50 w-fit px-4 py-2 rounded-full border border-orange-100 mt-4 animate-bounce-subtle">
                    <Award className="h-4 w-4 text-orange-500" />
                    <span className="text-sm font-bold text-orange-700">{streak} Day Streak! Keep it going!</span>
                  </div>
                )}
              </div>
            ) : (
              <div className="animate-pulse h-20 bg-gray-100 rounded-xl"></div>
            )}
          </CardContent>
        </Card>
          </div>

          <div className="col-span-1">
            {/* Weight Hub Card */}
            <Card className="bg-gradient-to-br from-emerald-50 to-teal-50 border-0 shadow-lg shadow-emerald-500/5 h-full relative overflow-hidden">
              <div className="absolute top-0 right-0 p-4 opacity-5 pointer-events-none">
                <Target className="w-32 h-32" />
              </div>
              <CardHeader>
                <CardTitle className="text-emerald-900 flex items-center">
                  <Scale className="mr-2 h-5 w-5 text-emerald-600" />
                  Weight Hub
                </CardTitle>
              </CardHeader>
              <CardContent className="relative z-10">
                {user ? (
                  <div className="space-y-6">
                    <div className="flex justify-between items-end">
                      <div>
                        <p className="text-sm text-emerald-600/70 font-medium mb-1">Current Weight</p>
                        <p className="text-3xl font-black text-emerald-900">{user.weight_kg || '-'} <span className="text-lg font-bold text-emerald-700/50">kg</span></p>
                      </div>
                    </div>
                    
                    <div className="flex justify-between items-end pt-4 border-t border-emerald-200/50">
                      <div>
                        <p className="text-sm text-emerald-600/70 font-medium mb-1">Target Weight</p>
                        <p className="text-2xl font-black text-emerald-800">{user.target_weight_kg || '-'} <span className="text-base font-bold text-emerald-700/50">kg</span></p>
                      </div>
                    </div>

                    {user.weight_kg && user.target_weight_kg && (
                      <div className="mt-4 p-3 bg-emerald-100 rounded-xl">
                        <p className="text-sm font-bold text-emerald-800 text-center">
                          {Math.abs((parseFloat(user.weight_kg) - parseFloat(user.target_weight_kg)).toFixed(1))} kg to go!
                        </p>
                      </div>
                    )}
                  </div>
                ) : (
                  <div className="animate-pulse h-20 bg-emerald-100/50 rounded-xl"></div>
                )}
              </CardContent>
            </Card>
          </div>
        </div>

        {/* Progress Chart Section */}
        <section className="animate-in slide-in-from-bottom duration-700">
           <ProgressChart data={historyData} period={period} setPeriod={setPeriod} />
        </section>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
          
          {/* Add Food */}
          <div className="space-y-4">
            <h3 className="text-xl font-bold text-gray-900 ml-1">Log Food</h3>
            <Card>
              <CardContent className="p-4">
                <form onSubmit={handleSearch} className="flex gap-2">
                  <Input 
                    placeholder="Search foods (e.g. apple, chicken)..." 
                    value={search}
                    onChange={e => setSearch(e.target.value)}
                    className="flex-1"
                  />
                  <Button type="submit" isLoading={isSearching} className="px-4">
                    <Search className="h-5 w-5" />
                  </Button>
                </form>

                {searchResults.length > 0 && (
                  <div className="mt-4 space-y-3">
                    {searchResults.map(food => (
                      <div key={food.id} className="flex flex-col sm:flex-row items-start sm:items-center justify-between p-3 rounded-xl border border-gray-100 hover:bg-gray-50 transition-colors gap-3">
                        <div className="flex-1 w-full">
                          <div className="flex items-center gap-2">
                            <p className="font-semibold text-gray-800">{food.name}</p>
                            {food.source === 'user' && (
                              <span className="text-[10px] font-semibold bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded-full uppercase tracking-wide">Custom</span>
                            )}
                          </div>
                          <p className="text-xs text-gray-500">{food.calories_per_100g} kcal / 100g</p>
                        </div>
                        <div className="flex items-center gap-2">
                          <div className="flex items-center bg-gray-100 rounded-lg px-2 h-9 border border-gray-200 focus-within:ring-2 focus-within:ring-emerald-500/50">
                            <input 
                              type="number" 
                              min="1" 
                              className="w-16 bg-transparent text-sm font-medium text-right outline-none pr-1"
                              value={grams[food.id]}
                              onChange={(e) => setGrams(prev => ({...prev, [food.id]: e.target.value}))}
                            />
                            <span className="text-xs text-gray-500 font-medium">g</span>
                          </div>
                          <Button size="icon" onClick={() => logFood(food.id)}>
                            <Plus className="h-5 w-5" />
                          </Button>
                        </div>
                      </div>
                    ))}
                  </div>
                )}

                {/* No results + Add Custom CTA */}
                {hasSearched && searchResults.length === 0 && !isSearching && (
                  <div className="mt-4 text-center py-6 space-y-3">
                    <p className="text-gray-500 text-sm">No foods found for "<span className="font-medium text-gray-700">{search}</span>"</p>
                    <Button onClick={() => setShowCustomModal(true)} variant="outline" className="mx-auto">
                      <Plus className="h-4 w-4 mr-1" /> Add Custom Food
                    </Button>
                  </div>
                )}

                {/* Always show the add custom button below results */}
                {searchResults.length > 0 && (
                  <div className="mt-3 pt-3 border-t border-gray-100">
                    <button 
                      onClick={() => setShowCustomModal(true)} 
                      className="text-sm text-emerald-600 font-medium hover:text-emerald-700 hover:underline transition-colors cursor-pointer"
                    >
                      + Add a custom food instead
                    </button>
                  </div>
                )}
              </CardContent>
            </Card>
          </div>

          {/* Today's Logs */}
          <div className="space-y-4">
            <h3 className="text-xl font-bold text-gray-900 ml-1">Today's Logs</h3>
            <Card>
              <CardContent className="p-4">
                {logs.length === 0 ? (
                  <p className="text-gray-500 text-center py-8 opacity-70">No foods logged today.</p>
                ) : (
                  <div className="space-y-3">
                    {logs.map(log => (
                      <div key={log.id} className="flex items-center justify-between p-3 rounded-xl bg-gray-50/50 border border-gray-100">
                        <div>
                          <div className="flex items-center gap-2">
                            <p className="font-semibold text-gray-800">{log.food.name}</p>
                            {log.food.source === 'user' && (
                              <span className="text-[10px] font-semibold bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded-full uppercase tracking-wide">Custom</span>
                            )}
                          </div>
                          <p className="text-xs text-gray-500">{log.grams}g &bull; {new Date(log.consumed_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</p>
                        </div>
                        <div className="flex items-center gap-4">
                          <span className="font-bold text-emerald-600">{log.calories} kcal</span>
                          <button 
                            onClick={() => deleteLog(log.id)}
                            className="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors cursor-pointer"
                          >
                            <Trash2 className="h-4 w-4" />
                          </button>
                        </div>
                      </div>
                    ))}
                  </div>
                )}
              </CardContent>
            </Card>
          </div>
          
        </div>
      </main>

      {/* Custom Food Modal */}
      <AddCustomFoodModal 
        isOpen={showCustomModal} 
        onClose={() => setShowCustomModal(false)} 
        onFoodCreated={handleCustomFoodCreated}
      />
    </div>
  );
}
