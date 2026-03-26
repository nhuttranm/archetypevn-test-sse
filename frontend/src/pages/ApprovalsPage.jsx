import { useState, useEffect } from 'react';
import { useAuth } from '../context/AuthContext';
import { purchaseOrderApi } from '../services/api';
import { StatusBadge, LoadingSpinner, EmptyState } from '../components/UI';
import { Link } from 'react-router-dom';
import { CheckCircle, Clock, Eye, ArrowRight } from 'lucide-react';

export default function ApprovalsPage() {
  const { user } = useAuth();
  const [pendingPOs, setPendingPOs] = useState([]);
  const [loading, setLoading] = useState(true);
  const [activeTab, setActiveTab] = useState('pending');

  useEffect(() => { fetchPendingPOs(); }, [activeTab]);

  const fetchPendingPOs = async () => {
    try {
      setLoading(true);
      const statusMap = { pending: getStatusForRole(user?.role), all: '' };
      const params = {};
      if (statusMap[activeTab]) params.status = statusMap[activeTab];
      const res = await purchaseOrderApi.list(params);
      setPendingPOs(res.data.data);
    } catch (err) { console.error(err); }
    finally { setLoading(false); }
  };

  function getStatusForRole(role) {
    const map = { manager: 'pending_manager', director: 'pending_director', finance: 'pending_finance' };
    return map[role] || '';
  }

  const tabs = [
    { key: 'pending', label: 'Awaiting My Review', icon: Clock },
    { key: 'all', label: 'All POs', icon: CheckCircle },
  ];

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl lg:text-3xl font-bold text-surface-900 tracking-tight">Approvals</h1>
        <p className="text-surface-500 mt-1">Review and approve purchase orders assigned to you.</p>
      </div>
      <div className="flex gap-1 bg-surface-100 p-1 rounded-xl w-fit">
        {tabs.map(tab => (
          <button key={tab.key} onClick={() => setActiveTab(tab.key)}
            className={`flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-medium transition-all ${activeTab === tab.key ? 'bg-white text-surface-900 shadow-sm' : 'text-surface-500 hover:text-surface-700'}`}>
            <tab.icon className="w-4 h-4" />{tab.label}
          </button>
        ))}
      </div>
      {loading ? <LoadingSpinner /> : pendingPOs.length === 0 ? (
        <EmptyState icon={CheckCircle} title="No pending approvals" description="You're all caught up!" />
      ) : (
        <div className="grid gap-4">
          {pendingPOs.map(po => (
            <div key={po.id} className="bg-white rounded-2xl border border-surface-200/50 p-6 hover:shadow-md transition-all group">
              <div className="flex flex-col sm:flex-row sm:items-center gap-4">
                <div className="flex-1">
                  <div className="flex items-center gap-3">
                    <span className="text-lg font-bold text-primary-600">{po.po_number}</span>
                    <StatusBadge status={po.status} />
                  </div>
                  <div className="mt-2 flex flex-wrap items-center gap-4 text-sm text-surface-500">
                    <span>Vendor: <strong className="text-surface-700">{po.vendor?.name}</strong></span>
                    <span>Dept: <strong className="text-surface-700">{po.department?.name}</strong></span>
                  </div>
                </div>
                <div className="flex items-center gap-4">
                  <p className="text-2xl font-bold text-surface-900">{po.total_amount_formatted}</p>
                  <Link to={`/purchase-orders/${po.id}`}
                    className="flex items-center gap-2 px-4 py-2.5 bg-primary-600 text-white rounded-xl text-sm font-semibold hover:bg-primary-700 transition-all shadow-sm">
                    <Eye className="w-4 h-4" />Review<ArrowRight className="w-3.5 h-3.5" />
                  </Link>
                </div>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
