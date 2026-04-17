import { Link, NavLink } from 'react-router-dom';
import useAuthStore from '../../store/authStore';
import { LogOut, Activity, Settings as SettingsIcon } from 'lucide-react';

export default function Navbar() {
  const { user, logout } = useAuthStore();

  const activeClass = "inline-flex items-center px-1 pt-1 border-b-2 border-emerald-500 text-sm font-medium text-gray-900";
  const inactiveClass = "inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700";

  return (
    <nav className="bg-white/80 backdrop-blur-lg border-b border-gray-200 sticky top-0 z-50">
      <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex justify-between h-16">
          <div className="flex">
            <Link to="/dashboard" className="flex-shrink-0 flex items-center">
              <Activity className="h-8 w-8 text-emerald-500 mr-2" />
              <span className="font-bold text-xl text-gray-900 tracking-tight">MyFitnessPal</span>
            </Link>
            <div className="hidden sm:ml-8 sm:flex sm:space-x-8">
              <NavLink 
                to="/dashboard" 
                className={({ isActive }) => isActive ? activeClass : inactiveClass}
              >
                Dashboard
              </NavLink>
              <NavLink 
                to="/history" 
                className={({ isActive }) => isActive ? activeClass : inactiveClass}
              >
                History
              </NavLink>
              <NavLink 
                to="/settings" 
                className={({ isActive }) => isActive ? activeClass : inactiveClass}
              >
                Settings
              </NavLink>
            </div>
          </div>
          <div className="flex items-center">
            {user && (
              <div className="flex items-center space-x-4">
                <Link 
                  to="/settings"
                  className="flex items-center text-sm font-medium text-gray-700 hover:text-emerald-600 transition-colors gap-2"
                >
                  <span className="hidden sm:block">Hello, {user.name}</span>
                  <div className="p-1.5 bg-gray-100 rounded-lg group-hover:bg-emerald-50">
                    <SettingsIcon className="h-4 w-4" />
                  </div>
                </Link>
                <button
                  onClick={logout}
                  className="p-2 text-gray-500 hover:text-red-500 hover:bg-red-50 rounded-full transition-colors"
                  title="Logout"
                >
                  <LogOut className="h-5 w-5" />
                </button>
              </div>
            )}
          </div>
        </div>
      </div>
    </nav>
  );
}
