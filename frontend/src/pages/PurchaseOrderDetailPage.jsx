import { useState, useEffect } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import { purchaseOrderApi, auditLogApi } from '../services/api';
import { StatusBadge, Button, LoadingSpinner } from '../components/UI';
import {
  ArrowLeft, CheckCircle, XCircle, Send, Copy,
  Clock, User, FileText, MessageSquare, ChevronRight
} from 'lucide-react';

const statusSteps = [
  { key: 'draft', label: 'Draft' },
  { key: 'pending_manager', label: 'Manager Review' },
  { key: 'pending_director', label: 'Director Review' },
  { key: 'pending_finance', label: 'Finance Review' },
  { key: 'approved', label: 'Approved' },
];

export default function PurchaseOrderDetailPage() {
  const { id } = useParams();
  const { user } = useAuth();
  const navigate = useNavigate();
  const [po, setPo] = useState(null);
  const [logs, setLogs] = useState([]);
  const [loading, setLoading] = useState(true);
  const [actionLoading, setActionLoading] = useState('');
  const [showRejectModal, setShowRejectModal] = useState(false);
  const [rejectReason, setRejectReason] = useState('');
  const [approveComment, setApproveComment] = useState('');

  useEffect(() => {
    fetchData();
  }, [id]);

  const fetchData = async () => {
    try {
      setLoading(true);
      const [poRes, logsRes] = await Promise.all([
        purchaseOrderApi.get(id),
        auditLogApi.getByPo(id),
      ]);
      setPo(poRes.data.data);
      setLogs(logsRes.data.data);
    } catch (err) {
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  const handleSubmit = async () => {
    setActionLoading('submit');
    try {
      await purchaseOrderApi.submit(id);
      await fetchData();
    } catch (err) {
      alert(err.response?.data?.message || 'Failed to submit');
    } finally {
      setActionLoading('');
    }
  };

  const handleApprove = async () => {
    setActionLoading('approve');
    try {
      await purchaseOrderApi.approve(id, { comment: approveComment || undefined });
      setApproveComment('');
      await fetchData();
    } catch (err) {
      alert(err.response?.data?.message || 'Failed to approve');
    } finally {
      setActionLoading('');
    }
  };

  const handleReject = async () => {
    if (!rejectReason.trim()) return;
    setActionLoading('reject');
    try {
      await purchaseOrderApi.reject(id, { reason: rejectReason });
      setShowRejectModal(false);
      setRejectReason('');
      await fetchData();
    } catch (err) {
      alert(err.response?.data?.message || 'Failed to reject');
    } finally {
      setActionLoading('');
    }
  };

  const handleRevise = async () => {
    setActionLoading('revise');
    try {
      const res = await purchaseOrderApi.revise(id);
      navigate(`/purchase-orders/${res.data.data.id}`);
    } catch (err) {
      alert(err.response?.data?.message || 'Failed to create revision');
    } finally {
      setActionLoading('');
    }
  };

  if (loading) return <LoadingSpinner />;
  if (!po) return <div className="text-center py-12 text-surface-500">Purchase order not found.</div>;

  const currentStepIndex = statusSteps.findIndex(s => s.key === po.status);
  const isRejected = po.status === 'rejected';

  return (
    <div className="max-w-5xl mx-auto space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center gap-4">
        <button onClick={() => navigate(-1)} className="p-2 rounded-xl border border-surface-300 hover:bg-surface-50 transition w-fit">
          <ArrowLeft className="w-5 h-5 text-surface-500" />
        </button>
        <div className="flex-1">
          <div className="flex items-center gap-3">
            <h1 className="text-2xl font-bold text-surface-900">{po.po_number}</h1>
            <StatusBadge status={po.status} />
            {po.revision_number > 1 && (
              <span className="text-xs bg-surface-100 text-surface-500 px-2 py-1 rounded-full">Rev {po.revision_number}</span>
            )}
          </div>
          <p className="text-sm text-surface-500 mt-1">Created by {po.creator?.name} • {new Date(po.created_at).toLocaleString()}</p>
        </div>
      </div>

      {/* Approval Progress */}
      <div className="bg-white rounded-2xl border border-surface-200/50 p-6">
        <h2 className="text-sm font-semibold text-surface-500 uppercase tracking-wider mb-4">Approval Progress</h2>
        <div className="flex items-center gap-2 overflow-x-auto pb-2">
          {statusSteps.map((step, index) => {
            const isPast = index < currentStepIndex;
            const isCurrent = index === currentStepIndex;
            const isFuture = index > currentStepIndex;

            return (
              <div key={step.key} className="flex items-center min-w-0">
                <div className={`flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-medium whitespace-nowrap transition-all
                  ${isPast ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : ''}
                  ${isCurrent && !isRejected ? 'bg-primary-50 text-primary-700 ring-2 ring-primary-300 shadow-sm' : ''}
                  ${isCurrent && isRejected ? 'bg-red-50 text-red-700 ring-2 ring-red-300' : ''}
                  ${isFuture ? 'bg-surface-50 text-surface-400' : ''}
                `}>
                  {isPast && <CheckCircle className="w-4 h-4 text-emerald-500" />}
                  {isCurrent && !isRejected && <Clock className="w-4 h-4 text-primary-500 animate-pulse" />}
                  {isCurrent && isRejected && <XCircle className="w-4 h-4 text-red-500" />}
                  {step.label}
                </div>
                {index < statusSteps.length - 1 && (
                  <ChevronRight className={`w-4 h-4 mx-1 flex-shrink-0 ${isPast ? 'text-emerald-400' : 'text-surface-300'}`} />
                )}
              </div>
            );
          })}
        </div>
      </div>

      {/* Actions */}
      {(po.can_submit || po.is_pending || po.is_rejected) && (
        <div className="bg-white rounded-2xl border border-surface-200/50 p-6 space-y-4">
          <h2 className="text-sm font-semibold text-surface-500 uppercase tracking-wider">Actions</h2>

          {po.can_submit && (
            <div className="flex items-center gap-3">
              <Button onClick={handleSubmit} loading={actionLoading === 'submit'}>
                <Send className="w-4 h-4" /> Submit for Approval
              </Button>
            </div>
          )}

          {po.is_pending && (
            <div className="space-y-3">
              <div>
                <label className="text-sm font-medium text-surface-700">Comment (optional)</label>
                <input
                  type="text"
                  value={approveComment}
                  onChange={(e) => setApproveComment(e.target.value)}
                  className="mt-1 w-full px-4 py-2.5 rounded-xl border border-surface-300 bg-surface-50 text-sm focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 outline-none"
                  placeholder="Add a comment..."
                />
              </div>
              <div className="flex items-center gap-3">
                <Button variant="success" onClick={handleApprove} loading={actionLoading === 'approve'}>
                  <CheckCircle className="w-4 h-4" /> Approve
                </Button>
                <Button variant="danger" onClick={() => setShowRejectModal(true)}>
                  <XCircle className="w-4 h-4" /> Reject
                </Button>
              </div>
            </div>
          )}

          {po.is_rejected && (
            <Button onClick={handleRevise} loading={actionLoading === 'revise'} variant="secondary">
              <Copy className="w-4 h-4" /> Create Revision
            </Button>
          )}
        </div>
      )}

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Details */}
        <div className="lg:col-span-2 space-y-6">
          {/* PO Info */}
          <div className="bg-white rounded-2xl border border-surface-200/50 p-6">
            <h2 className="text-lg font-semibold text-surface-900 mb-4">Details</h2>
            <dl className="grid grid-cols-2 gap-4">
              <div>
                <dt className="text-xs font-medium text-surface-500">Vendor</dt>
                <dd className="mt-1 text-sm font-medium text-surface-900">{po.vendor?.name}</dd>
              </div>
              <div>
                <dt className="text-xs font-medium text-surface-500">Department</dt>
                <dd className="mt-1 text-sm font-medium text-surface-900">{po.department?.name}</dd>
              </div>
              <div>
                <dt className="text-xs font-medium text-surface-500">Total Amount</dt>
                <dd className="mt-1 text-lg font-bold text-surface-900">{po.total_amount_formatted}</dd>
              </div>
              <div>
                <dt className="text-xs font-medium text-surface-500">Created By</dt>
                <dd className="mt-1 text-sm font-medium text-surface-900">{po.creator?.name}</dd>
              </div>
              {po.notes && (
                <div className="col-span-2">
                  <dt className="text-xs font-medium text-surface-500">Notes</dt>
                  <dd className="mt-1 text-sm text-surface-700">{po.notes}</dd>
                </div>
              )}
              {po.rejection_reason && (
                <div className="col-span-2 p-3 rounded-xl bg-red-50 border border-red-100">
                  <dt className="text-xs font-medium text-red-600">Rejection Reason</dt>
                  <dd className="mt-1 text-sm text-red-800">{po.rejection_reason}</dd>
                </div>
              )}
            </dl>
          </div>

          {/* Line Items */}
          <div className="bg-white rounded-2xl border border-surface-200/50 overflow-hidden">
            <div className="px-6 py-4 border-b border-surface-100">
              <h2 className="text-lg font-semibold text-surface-900">Line Items</h2>
            </div>
            <table className="w-full">
              <thead>
                <tr className="border-b border-surface-100">
                  <th className="px-6 py-3 text-left text-xs font-semibold text-surface-500 uppercase">Description</th>
                  <th className="px-6 py-3 text-right text-xs font-semibold text-surface-500 uppercase">Qty</th>
                  <th className="px-6 py-3 text-right text-xs font-semibold text-surface-500 uppercase">Unit Price</th>
                  <th className="px-6 py-3 text-right text-xs font-semibold text-surface-500 uppercase">Total</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-surface-100">
                {po.items?.map((item) => (
                  <tr key={item.id} className="hover:bg-surface-50/50">
                    <td className="px-6 py-3 text-sm text-surface-900">{item.description}</td>
                    <td className="px-6 py-3 text-sm text-surface-700 text-right">{item.quantity} {item.unit}</td>
                    <td className="px-6 py-3 text-sm text-surface-700 text-right">${item.unit_price.toLocaleString(undefined, { minimumFractionDigits: 2 })}</td>
                    <td className="px-6 py-3 text-sm font-semibold text-surface-900 text-right">${item.total_price.toLocaleString(undefined, { minimumFractionDigits: 2 })}</td>
                  </tr>
                ))}
              </tbody>
              <tfoot>
                <tr className="border-t-2 border-surface-200">
                  <td colSpan="3" className="px-6 py-3 text-sm font-semibold text-surface-900 text-right">Total</td>
                  <td className="px-6 py-3 text-lg font-bold text-surface-900 text-right">{po.total_amount_formatted}</td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

        {/* Audit Log Sidebar */}
        <div className="space-y-6">
          <div className="bg-white rounded-2xl border border-surface-200/50 p-6">
            <h2 className="text-lg font-semibold text-surface-900 mb-4">Activity</h2>
            <div className="space-y-4">
              {logs.map((log, index) => (
                <div key={log.id} className="relative pl-6 animate-slide-in" style={{ animationDelay: `${index * 50}ms` }}>
                  {index < logs.length - 1 && (
                    <div className="absolute left-[9px] top-6 bottom-0 w-px bg-surface-200" />
                  )}
                  <div className={`absolute left-0 top-1.5 w-[18px] h-[18px] rounded-full border-2 flex items-center justify-center
                    ${log.to_status === 'approved' ? 'border-emerald-500 bg-emerald-50' :
                      log.to_status === 'rejected' ? 'border-red-500 bg-red-50' :
                      'border-primary-500 bg-primary-50'
                    }`}>
                    <div className={`w-2 h-2 rounded-full
                      ${log.to_status === 'approved' ? 'bg-emerald-500' :
                        log.to_status === 'rejected' ? 'bg-red-500' :
                        'bg-primary-500'
                      }`}
                    />
                  </div>
                  <div>
                    <div className="flex items-center gap-2">
                      <StatusBadge status={log.to_status} />
                    </div>
                    <p className="mt-1 text-sm text-surface-700">{log.comment}</p>
                    <div className="mt-1 flex items-center gap-2 text-xs text-surface-400">
                      <User className="w-3 h-3" />
                      {log.actor?.name || 'System'}
                      <span>•</span>
                      <span>{log.created_at_human}</span>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>

      {/* Reject Modal */}
      {showRejectModal && (
        <div className="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 animate-fade-in">
            <h3 className="text-lg font-bold text-surface-900">Reject Purchase Order</h3>
            <p className="text-sm text-surface-500 mt-1">Please provide a reason for rejection.</p>
            <textarea
              value={rejectReason}
              onChange={(e) => setRejectReason(e.target.value)}
              className="mt-4 w-full px-4 py-3 rounded-xl border border-surface-300 bg-surface-50 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 outline-none resize-none"
              rows={4}
              placeholder="Enter rejection reason..."
            />
            <div className="flex justify-end gap-3 mt-4">
              <Button variant="secondary" onClick={() => setShowRejectModal(false)}>Cancel</Button>
              <Button variant="danger" onClick={handleReject} loading={actionLoading === 'reject'} disabled={!rejectReason.trim()}>
                Reject PO
              </Button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
