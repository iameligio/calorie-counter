import React, { useState, useEffect } from 'react';
import Navbar from '../components/layout/Navbar';
import useAuthStore from '../store/authStore';
import { Card, CardContent, CardHeader, CardTitle } from '../components/common/Card';
import { Button } from '../components/common/Button';
import { Input } from '../components/common/Input';
import { User, Activity, Scale, Ruler, Target, Save, CheckCircle } from 'lucide-react';
import axiosClient from '../services/axiosClient';

const ACTIVITY_LEVELS = [
  { id: 'sedentary', label: 'Sedentary', desc: 'Little or no exercise' },
  { id: 'lightly_active', label: 'Light', desc: 'Exercise 1-3 times/week' },
  { id: 'moderately_active', label: 'Moderate', desc: 'Exercise 4-5 times/week' },
  { id: 'very_active', label: 'Active', desc: 'Daily exercise or intense exercise' },
  { id: 'extra_active', label: 'Very Active', desc: 'Intense exercise 6-7 times/week' },
];

const GOALS = [
  { id: 'lose', label: 'Lose Weight', desc: '-500 kcal deficit' },
  { id: 'maintain', label: 'Maintain', desc: 'Maintain current weight' },
  { id: 'gain', label: 'Gain Muscle', desc: '+500 kcal surplus' },
];

export default function Settings() {
  const { user, fetchUser } = useAuthStore();
  const [formData, setFormData] = useState({
    gender: 'male',
    age: '',
    weight_kg: '',
    target_weight_kg: '',
    height_cm: '',
    activity_level: 'sedentary',
    goal: 'maintain'
  });
  
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [success, setSuccess] = useState(false);
  const [calculatedTarget, setCalculatedTarget] = useState(2000);

  useEffect(() => {
    if (user) {
      setFormData({
        gender: user.gender || 'male',
        age: user.age || '',
        weight_kg: user.weight_kg || '',
        target_weight_kg: user.target_weight_kg || '',
        height_cm: user.height_cm || '',
        activity_level: user.activity_level || 'sedentary',
        goal: user.goal || 'maintain'
      });
    }
  }, [user]);

  // Real-time calculation preview
  useEffect(() => {
    const { gender, age, weight_kg, height_cm, activity_level, goal } = formData;
    if (age && weight_kg && height_cm) {
        let bmr;
        const w = parseFloat(weight_kg);
        const h = parseFloat(height_cm);
        const a = parseInt(age);

        if (gender === 'male') {
            bmr = (10 * w) + (6.25 * h) - (5 * a) + 5;
        } else {
            bmr = (10 * w) + (6.25 * h) - (5 * a) - 161;
        }

        const multipliers = {
            sedentary: 1.2,
            lightly_active: 1.375,
            moderately_active: 1.55,
            very_active: 1.725,
            extra_active: 1.9,
        };

        let tdee = bmr * (multipliers[activity_level] || 1.2);
        if (goal === 'lose') tdee -= 500;
        if (goal === 'gain') tdee += 500;

        setCalculatedTarget(Math.max(1200, Math.round(tdee)));
    }
  }, [formData]);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setIsSubmitting(true);
    setSuccess(false);
    try {
      await axiosClient.put('/profile', formData);
      await fetchUser();
      setSuccess(true);
      setTimeout(() => setSuccess(false), 3000);
    } catch (err) {
      console.error(err);
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className="min-h-screen bg-gray-50 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] pb-20">
      <Navbar />
      
      <main className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 mt-8 space-y-8">
        <div className="flex items-center gap-4">
            <div className="p-3 bg-emerald-100 rounded-2xl text-emerald-600 shadow-sm">
                <User className="h-6 w-6" />
            </div>
            <div>
                <h1 className="text-3xl font-black text-gray-900 tracking-tight">Profile Settings</h1>
                <p className="text-sm text-gray-500 font-medium">Personalize your calorie target calculation</p>
            </div>
        </div>

        <form onSubmit={handleSubmit} className="space-y-6">
            <Card className="border-0 shadow-xl shadow-emerald-500/5">
                <CardHeader>
                    <CardTitle className="text-lg flex items-center gap-2">
                        <Activity className="h-5 w-5 text-emerald-500" />
                        Biological Factors
                    </CardTitle>
                </CardHeader>
                <CardContent className="space-y-6">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div className="space-y-2">
                            <label className="text-sm font-bold text-gray-700 ml-1">Gender</label>
                            <div className="flex gap-2">
                                {['male', 'female', 'other'].map(g => (
                                    <button
                                        key={g}
                                        type="button"
                                        onClick={() => setFormData({...formData, gender: g})}
                                        className={`flex-1 py-3 px-4 rounded-xl text-sm font-bold capitalize transition-all border ${
                                            formData.gender === g 
                                            ? 'bg-emerald-50 border-emerald-200 text-emerald-700 shadow-sm' 
                                            : 'bg-white border-gray-100 text-gray-500 hover:border-gray-300'
                                        }`}
                                    >
                                        {g}
                                    </button>
                                ))}
                            </div>
                        </div>
                        <Input 
                            label="Age" 
                            type="number" 
                            required 
                            value={formData.age}
                            onChange={e => setFormData({...formData, age: e.target.value})}
                            placeholder="e.g. 25"
                        />
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div className="relative">
                            <Input 
                                label="Weight (kg)" 
                                type="number" 
                                step="0.1"
                                required 
                                value={formData.weight_kg}
                                onChange={e => setFormData({...formData, weight_kg: e.target.value})}
                                placeholder="e.g. 75"
                            />
                            <Scale className="absolute right-4 top-10 h-5 w-5 text-gray-300 pointer-events-none" />
                        </div>
                        <div className="relative">
                            <Input 
                                label="Target Weight (kg)" 
                                type="number" 
                                step="0.1"
                                value={formData.target_weight_kg}
                                onChange={e => setFormData({...formData, target_weight_kg: e.target.value})}
                                placeholder="e.g. 70"
                            />
                            <Target className="absolute right-4 top-10 h-5 w-5 text-gray-300 pointer-events-none" />
                        </div>
                        <div className="relative">
                            <Input 
                                label="Height (cm)" 
                                type="number" 
                                required 
                                value={formData.height_cm}
                                onChange={e => setFormData({...formData, height_cm: e.target.value})}
                                placeholder="e.g. 175"
                            />
                            <Ruler className="absolute right-4 top-10 h-5 w-5 text-gray-300 pointer-events-none" />
                        </div>
                    </div>
                </CardContent>
            </Card>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <Card className="border-0 shadow-lg">
                    <CardHeader>
                        <CardTitle className="text-base flex items-center gap-2">
                            <Activity className="h-4 w-4 text-emerald-500" />
                            Activity Level
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {ACTIVITY_LEVELS.map(level => (
                            <button
                                key={level.id}
                                type="button"
                                onClick={() => setFormData({...formData, activity_level: level.id})}
                                className={`w-full text-left p-3 rounded-xl border transition-all ${
                                    formData.activity_level === level.id 
                                    ? 'border-emerald-500 bg-emerald-50/50' 
                                    : 'border-gray-100 hover:border-gray-200 bg-white'
                                }`}
                            >
                                <p className={`text-sm font-bold ${formData.activity_level === level.id ? 'text-emerald-700' : 'text-gray-700'}`}>
                                    {level.label}
                                </p>
                                <p className="text-[11px] text-gray-400 font-medium">{level.desc}</p>
                            </button>
                        ))}
                    </CardContent>
                </Card>

                <Card className="border-0 shadow-lg">
                    <CardHeader>
                        <CardTitle className="text-base flex items-center gap-2">
                            <Target className="h-4 w-4 text-emerald-500" />
                            Fitness Goal
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {GOALS.map(goal => (
                            <button
                                key={goal.id}
                                type="button"
                                onClick={() => setFormData({...formData, goal: goal.id})}
                                className={`w-full text-left p-3 rounded-xl border transition-all ${
                                    formData.goal === goal.id 
                                    ? 'border-emerald-500 bg-emerald-50/50' 
                                    : 'border-gray-100 hover:border-gray-200 bg-white'
                                }`}
                            >
                                <p className={`text-sm font-bold ${formData.goal === goal.id ? 'text-emerald-700' : 'text-gray-700'}`}>
                                    {goal.label}
                                </p>
                                <p className="text-[11px] text-gray-400 font-medium">{goal.desc}</p>
                            </button>
                        ))}
                    </CardContent>
                </Card>
            </div>

            {/* Recommendation Preview Card */}
            <Card className="bg-emerald-900 border-0 shadow-2xl text-white overflow-hidden relative">
                <div className="absolute right-0 top-0 w-32 h-32 bg-emerald-400/10 rounded-full -mr-16 -mt-16 blur-3xl"></div>
                <CardContent className="p-8">
                    <div className="flex flex-col md:flex-row items-center justify-between gap-8">
                        <div>
                            <p className="text-emerald-300 text-xs font-black uppercase tracking-widest mb-2">Recommended Target</p>
                            <div className="flex items-baseline gap-2">
                                <span className="text-5xl font-black">{calculatedTarget}</span>
                                <span className="text-emerald-300 text-lg font-bold">kcal / day</span>
                            </div>
                        </div>
                        <div className="flex flex-col gap-4 w-full md:w-auto">
                            <Button 
                                type="submit" 
                                className="bg-white text-emerald-900 hover:bg-emerald-50 px-8 h-12 rounded-xl text-base"
                                isLoading={isSubmitting}
                            >
                                {success ? (
                                    <span className="flex items-center gap-2 animate-in zoom-in">
                                        <CheckCircle className="h-5 w-5" /> Saved!
                                    </span>
                                ) : (
                                    <span className="flex items-center gap-2">
                                        <Save className="h-5 w-5" /> Update Profile
                                    </span>
                                )}
                            </Button>
                            <p className="text-[10px] text-emerald-400/70 text-center italic">
                                * Calculated using Mifflin-St Jeor formula
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </form>
      </main>
    </div>
  );
}
