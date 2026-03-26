import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider, useAuth } from './context/AuthContext';
import MainLayout from './layouts/MainLayout';
import LoginPage from './pages/LoginPage';
import DashboardPage from './pages/DashboardPage';
import CreatePurchaseOrderPage from './pages/CreatePurchaseOrderPage';
import PurchaseOrderDetailPage from './pages/PurchaseOrderDetailPage';
import ApprovalsPage from './pages/ApprovalsPage';
import AuditLogPage from './pages/AuditLogPage';
import WorkflowConfigPage from './pages/WorkflowConfigPage';

function ProtectedRoute({ children }) {
  const { user, loading } = useAuth();
  if (loading) return (
    <div className="min-h-screen flex items-center justify-center bg-surface-50">
      <div className="w-10 h-10 border-3 border-surface-200 border-t-primary-500 rounded-full animate-spin" />
    </div>
  );
  if (!user) return <Navigate to="/login" replace />;
  return children;
}

function PublicRoute({ children }) {
  const { user, loading } = useAuth();
  if (loading) return null;
  if (user) return <Navigate to="/" replace />;
  return children;
}

export default function App() {
  return (
    <BrowserRouter>
      <AuthProvider>
        <Routes>
          <Route path="/login" element={<PublicRoute><LoginPage /></PublicRoute>} />
          <Route path="/" element={<ProtectedRoute><MainLayout /></ProtectedRoute>}>
            <Route index element={<DashboardPage />} />
            <Route path="purchase-orders/create" element={<CreatePurchaseOrderPage />} />
            <Route path="purchase-orders/:id" element={<PurchaseOrderDetailPage />} />
            <Route path="approvals" element={<ApprovalsPage />} />
            <Route path="audit-logs" element={<AuditLogPage />} />
            <Route path="workflow" element={<WorkflowConfigPage />} />
          </Route>
          <Route path="*" element={<Navigate to="/" replace />} />
        </Routes>
      </AuthProvider>
    </BrowserRouter>
  );
}
