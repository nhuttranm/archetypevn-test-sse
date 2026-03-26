import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import { FileText, Eye, EyeOff } from 'lucide-react';
import { Button } from '../components/UI';

export default function LoginPage() {
  const { login } = useAuth();
  const navigate = useNavigate();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const [showPassword, setShowPassword] = useState(false);

  const demoAccounts = [
    { role: 'Staff', email: 'staff@example.com', color: 'from-blue-500 to-blue-600' },
    { role: 'Manager', email: 'manager@example.com', color: 'from-emerald-500 to-emerald-600' },
    { role: 'Director', email: 'director@example.com', color: 'from-purple-500 to-purple-600' },
    { role: 'Finance', email: 'finance@example.com', color: 'from-amber-500 to-amber-600' },
  ];

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setLoading(true);
    try {
      await login(email, password);
      navigate('/');
    } catch (err) {
      setError(err.response?.data?.message || 'Invalid credentials');
    } finally {
      setLoading(false);
    }
  };

  const handleDemoLogin = (demoEmail) => {
    setEmail(demoEmail);
    setPassword('password');
  };

  return (
    <div className="min-h-screen flex">
      {/* Left Panel - Branding */}
      <div className="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-primary-600 via-primary-700 to-primary-900 relative overflow-hidden">
        <div className="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxnIGZpbGw9IiNmZmYiIGZpbGwtb3BhY2l0eT0iMC4wNSI+PGNpcmNsZSBjeD0iMzAiIGN5PSIzMCIgcj0iMiIvPjwvZz48L2c+PC9zdmc+')] opacity-50" />
        <div className="relative z-10 flex flex-col justify-center px-12 lg:px-16">
          <div className="w-16 h-16 rounded-2xl bg-white/10 backdrop-blur-sm flex items-center justify-center mb-8 ring-1 ring-white/20">
            <FileText className="w-8 h-8 text-white" />
          </div>
          <h1 className="text-4xl lg:text-5xl font-bold text-white leading-tight">
            Purchase Order<br />Management System
          </h1>
          <p className="mt-6 text-lg text-primary-200 leading-relaxed max-w-lg">
            Streamline your procurement workflow with dynamic approval chains,
            real-time tracking, and comprehensive audit trails.
          </p>
          <div className="mt-12 flex items-center gap-6 text-sm text-primary-300">
            <div className="flex items-center gap-2">
              <div className="w-2 h-2 rounded-full bg-emerald-400 animate-pulse" />
              Role-based Access
            </div>
            <div className="flex items-center gap-2">
              <div className="w-2 h-2 rounded-full bg-emerald-400 animate-pulse" />
              Dynamic Workflow
            </div>
            <div className="flex items-center gap-2">
              <div className="w-2 h-2 rounded-full bg-emerald-400 animate-pulse" />
              Audit Trail
            </div>
          </div>
        </div>
      </div>

      {/* Right Panel - Login Form */}
      <div className="flex-1 flex items-center justify-center p-8 bg-surface-50">
        <div className="w-full max-w-md">
          <div className="lg:hidden mb-8 text-center">
            <div className="w-12 h-12 rounded-xl bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center mx-auto mb-4">
              <FileText className="w-6 h-6 text-white" />
            </div>
            <h1 className="text-2xl font-bold text-surface-900">PO System</h1>
          </div>

          <div className="bg-white rounded-2xl shadow-xl shadow-surface-200/50 border border-surface-200/50 p-8">
            <h2 className="text-2xl font-bold text-surface-900">Welcome back</h2>
            <p className="mt-2 text-sm text-surface-500">Sign in to your account to continue</p>

            {error && (
              <div className="mt-4 p-3 rounded-xl bg-red-50 border border-red-100 text-red-600 text-sm animate-fade-in">
                {error}
              </div>
            )}

            <form onSubmit={handleSubmit} className="mt-6 space-y-4">
              <div>
                <label className="text-sm font-medium text-surface-700" htmlFor="email">Email</label>
                <input
                  id="email"
                  type="email"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  className="mt-1.5 w-full px-4 py-3 rounded-xl border border-surface-300 bg-surface-50 text-surface-900 placeholder-surface-400 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all outline-none"
                  placeholder="Enter your email"
                  required
                />
              </div>
              <div>
                <label className="text-sm font-medium text-surface-700" htmlFor="password">Password</label>
                <div className="relative mt-1.5">
                  <input
                    id="password"
                    type={showPassword ? 'text' : 'password'}
                    value={password}
                    onChange={(e) => setPassword(e.target.value)}
                    className="w-full px-4 py-3 rounded-xl border border-surface-300 bg-surface-50 text-surface-900 placeholder-surface-400 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all outline-none pr-12"
                    placeholder="Enter your password"
                    required
                  />
                  <button
                    type="button"
                    onClick={() => setShowPassword(!showPassword)}
                    className="absolute right-3 top-1/2 -translate-y-1/2 text-surface-400 hover:text-surface-600"
                  >
                    {showPassword ? <EyeOff className="w-5 h-5" /> : <Eye className="w-5 h-5" />}
                  </button>
                </div>
              </div>
              <Button type="submit" loading={loading} className="w-full mt-2" size="lg">
                Sign in
              </Button>
            </form>
          </div>

          {/* Demo accounts */}
          <div className="mt-6 bg-white rounded-2xl border border-surface-200/50 p-6">
            <p className="text-xs font-semibold text-surface-400 uppercase tracking-wider mb-3">Quick Demo Access</p>
            <div className="grid grid-cols-2 gap-2">
              {demoAccounts.map((account) => (
                <button
                  key={account.role}
                  onClick={() => handleDemoLogin(account.email)}
                  className="flex items-center gap-2 px-3 py-2.5 rounded-xl border border-surface-200 hover:border-primary-300 hover:bg-primary-50/50 transition-all text-left group"
                >
                  <div className={`w-8 h-8 rounded-lg bg-gradient-to-br ${account.color} flex items-center justify-center text-white text-xs font-bold shadow-sm`}>
                    {account.role.charAt(0)}
                  </div>
                  <div>
                    <p className="text-sm font-medium text-surface-700 group-hover:text-primary-700">{account.role}</p>
                    <p className="text-[10px] text-surface-400 truncate">{account.email}</p>
                  </div>
                </button>
              ))}
            </div>
            <p className="mt-3 text-[10px] text-surface-400 text-center">Password: <code className="bg-surface-100 px-1.5 py-0.5 rounded">password</code></p>
          </div>
        </div>
      </div>
    </div>
  );
}
