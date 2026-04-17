import React, { useState } from 'react';
import { Card, CardHeader, CardTitle, CardContent } from '../components/common/Card';
import { Input } from '../components/common/Input';
import { Button } from '../components/common/Button';
import useAuthStore from '../store/authStore';
import { useNavigate, Link } from 'react-router-dom';

export default function Register() {
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const { register, isLoading } = useAuthStore();
  const navigate = useNavigate();

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    try {
      await register(name, email, password);
      navigate('/dashboard');
    } catch (err) {
      setError(err.response?.data?.message || 'Registration failed. Please try again.');
    }
  };

  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-50 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] p-4">
      <Card className="w-full max-w-md">
        <CardHeader className="text-center pb-4">
          <CardTitle className="text-2xl text-emerald-600 font-bold">MyFitnessPal</CardTitle>
          <p className="text-sm text-gray-500 mt-2">Create an account to start tracking.</p>
        </CardHeader>
        <CardContent>
          <form onSubmit={handleSubmit} className="space-y-4">
            {error && <div className="p-3 bg-red-50 text-red-600 text-sm rounded-xl">{error}</div>}
            <Input 
              label="Name" 
              type="text" 
              required 
              value={name} 
              onChange={e => setName(e.target.value)} 
              placeholder="Your Name"
            />
            <Input 
              label="Email" 
              type="email" 
              required 
              value={email} 
              onChange={e => setEmail(e.target.value)} 
              placeholder="you@example.com"
            />
            <Input 
              label="Password (min 8 characters)" 
              type="password" 
              required 
              minLength={8}
              value={password} 
              onChange={e => setPassword(e.target.value)} 
            />
            <Button type="submit" className="w-full mt-2" isLoading={isLoading}>
              Sign Up
            </Button>
          </form>
          <div className="mt-6 text-center text-sm text-gray-600">
            Already have an account? <Link to="/login" className="text-emerald-600 font-semibold hover:underline">Sign in</Link>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
