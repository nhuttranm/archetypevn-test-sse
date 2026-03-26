import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import { purchaseOrderApi } from '../services/api';
import { StatusBadge, StatCard, EmptyState, LoadingSpinner } from '../components/UI';
import {
  FileText, Plus, TrendingUp, Clock, CheckCircle,
  XCircle, DollarSign, Filter, Search, Eye, ChevronLeft, ChevronRight
} from 'lucide-react';

export default function DashboardPage() {
  const { user } = useAuth();
  const [stats, setStats] = useState(null);
  const [purchaseOrders, setPurchaseOrders] = useState([]);
  const [loading, setLoading] = useState(true);
  const [filters, setFilters] = useState({ status: '', search: '', page: 1 });
  const [pagination, setPagination] = useState({});

  useEffect(() => {
    fetchData();
  }, [filters]);

  const fetchData = async () => {
    try {
      setLoading(true);
      const [statsRes, poRes] = await Promise.all([
        purchaseOrderApi.dashboard(),
        purchaseOrderApi.list({
          page: filters.page,
          per_page: 10,
          ...(filters.status && { status: filters.status }),
          ...(filters.search && { search: filters.search }),
        }),
      ]);
      setStats(statsRes.data.data);
      setPurchaseOrders(poRes.data.data);
      setPagination(poRes.data.meta || {});
    } catch (err) {
      console.error('Failed to fetch dashboard data:', err);
    } finally {
      setLoading(false);
    }
  };

  const statusOptions = [
    { value: '', label: 'All Statuses' },
    { value: 'draft', label: 'Draft' },
    { value: 'pending_manager', label: 'Pending Manager' },
    { value: 'pending_director', label: 'Pending Director' },
    { value: 'pending_finance', label: 'Pending Finance' },
    { value: 'approved', label: 'Approved' },
    { value: 'rejected', label: 'Rejected' },
  ];

  return (
    <div className="space-y-8">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-2xl lg:text-3xl font-bold text-surface-900 tracking-tight">Dashboard</h1>
          <p className="text-surface-500 mt-1">Welcome back, {user?.name}. Here's a snapshot of your purchase orders.</p>
        </div>
        <Link
          to="/purchase-orders/create"
          className="inline-flex items-center gap-2 px-5 py-3 bg-gradient-to-r from-primary-600 to-primary-700 text-white font-semibold rounded-xl shadow-lg shadow-primary-500/20 hover:shadow-primary-500/30 hover:from-primary-700 hover:to-primary-800 transition-all"
        >
          <Plus className="w-5 h-5" />
          New Purchase Order
        </Link>
      </div>

      {/* Stats */}
      {stats && (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
          <StatCard title="Total POs" value={stats.total} icon={FileText} color="primary" />
          <StatCard title="Draft" value={stats.draft} icon={FileText} color="info" />
          <StatCard title="Pending" value={stats.pending} icon={Clock} color="warning" />
          <StatCard title="Approved" value={stats.approved} icon={CheckCircle} color="success" />
          <StatCard title="Rejected" value={stats.rejected} icon={XCircle} color="danger" />
          <StatCard
            title="Total Value"
            value={`$${Number(stats.total_amount || 0).toLocaleString()}`}
            icon={DollarSign}
            color="purple"
          />
        </div>
      )}

      {/* Filters */}
      <div className="bg-white rounded-2xl border border-surface-200/50 p-4">
        <div className="flex flex-col sm:flex-row gap-3">
          <div className="relative flex-1">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400" />
            <input
              type="text"
              placeholder="Search by PO number..."
              value={filters.search}
              onChange={(e) => setFilters(f => ({ ...f, search: e.target.value, page: 1 }))}
              className="w-full pl-10 pr-4 py-2.5 rounded-xl border border-surface-300 bg-surface-50 text-sm focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 outline-none transition-all"
            />
          </div>
          <div className="flex items-center gap-2">
            <Filter className="w-4 h-4 text-surface-400" />
            <select
              value={filters.status}
              onChange={(e) => setFilters(f => ({ ...f, status: e.target.value, page: 1 }))}
              className="px-4 py-2.5 rounded-xl border border-surface-300 bg-surface-50 text-sm focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 outline-none transition-all"
            >
              {statusOptions.map(opt => (
                <option key={opt.value} value={opt.value}>{opt.label}</option>
              ))}
            </select>
          </div>
        </div>
      </div>

      {/* Purchase Orders Table */}
      <div className="bg-white rounded-2xl border border-surface-200/50 overflow-hidden">
        <div className="px-6 py-4 border-b border-surface-100">
          <h2 className="text-lg font-semibold text-surface-900">Purchase Orders</h2>
        </div>

        {loading ? (
          <LoadingSpinner />
        ) : purchaseOrders.length === 0 ? (
          <EmptyState
            icon={FileText}
            title="No purchase orders found"
            description="Create your first purchase order to get started."
            action={
              <Link
                to="/purchase-orders/create"
                className="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white rounded-xl text-sm font-medium hover:bg-primary-700 transition"
              >
                <Plus className="w-4 h-4" /> Create PO
              </Link>
            }
          />
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead>
                <tr className="border-b border-surface-100">
                  <th className="px-6 py-3 text-left text-xs font-semibold text-surface-500 uppercase tracking-wider">PO Number</th>
                  <th className="px-6 py-3 text-left text-xs font-semibold text-surface-500 uppercase tracking-wider">Vendor</th>
                  <th className="px-6 py-3 text-left text-xs font-semibold text-surface-500 uppercase tracking-wider">Department</th>
                  <th className="px-6 py-3 text-left text-xs font-semibold text-surface-500 uppercase tracking-wider">Amount</th>
                  <th className="px-6 py-3 text-left text-xs font-semibold text-surface-500 uppercase tracking-wider">Status</th>
                  <th className="px-6 py-3 text-left text-xs font-semibold text-surface-500 uppercase tracking-wider">Created</th>
                  <th className="px-6 py-3 text-right text-xs font-semibold text-surface-500 uppercase tracking-wider">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-surface-100">
                {purchaseOrders.map((po) => (
                  <tr key={po.id} className="hover:bg-surface-50/50 transition-colors group">
                    <td className="px-6 py-4">
                      <span className="text-sm font-semibold text-primary-600">{po.po_number}</span>
                      {po.revision_number > 1 && (
                        <span className="ml-1.5 text-[10px] bg-surface-100 text-surface-500 px-1.5 py-0.5 rounded-full">
                          Rev {po.revision_number}
                        </span>
                      )}
                    </td>
                    <td className="px-6 py-4 text-sm text-surface-700">{po.vendor?.name || '-'}</td>
                    <td className="px-6 py-4 text-sm text-surface-700">{po.department?.name || '-'}</td>
                    <td className="px-6 py-4 text-sm font-semibold text-surface-900">{po.total_amount_formatted}</td>
                    <td className="px-6 py-4"><StatusBadge status={po.status} /></td>
                    <td className="px-6 py-4 text-sm text-surface-500">
                      {new Date(po.created_at).toLocaleDateString()}
                    </td>
                    <td className="px-6 py-4 text-right">
                      <Link
                        to={`/purchase-orders/${po.id}`}
                        className="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-primary-600 hover:bg-primary-50 rounded-lg transition-colors opacity-0 group-hover:opacity-100"
                      >
                        <Eye className="w-3.5 h-3.5" /> View
                      </Link>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}

        {/* Pagination */}
        {pagination.last_page > 1 && (
          <div className="px-6 py-4 border-t border-surface-100 flex items-center justify-between">
            <p className="text-sm text-surface-500">
              Showing {pagination.from} to {pagination.to} of {pagination.total} results
            </p>
            <div className="flex items-center gap-2">
              <button
                onClick={() => setFilters(f => ({ ...f, page: f.page - 1 }))}
                disabled={filters.page <= 1}
                className="p-2 rounded-lg border border-surface-300 hover:bg-surface-50 disabled:opacity-50 disabled:cursor-not-allowed transition"
              >
                <ChevronLeft className="w-4 h-4" />
              </button>
              <span className="text-sm text-surface-600 font-medium px-3">
                Page {pagination.current_page} of {pagination.last_page}
              </span>
              <button
                onClick={() => setFilters(f => ({ ...f, page: f.page + 1 }))}
                disabled={filters.page >= pagination.last_page}
                className="p-2 rounded-lg border border-surface-300 hover:bg-surface-50 disabled:opacity-50 disabled:cursor-not-allowed transition"
              >
                <ChevronRight className="w-4 h-4" />
              </button>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
