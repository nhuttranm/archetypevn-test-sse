import { useState, useEffect } from 'react';
import { auditLogApi } from '../services/api';
import { StatusBadge, LoadingSpinner, EmptyState } from '../components/UI';
import { History, User, Clock, Search } from 'lucide-react';

export default function AuditLogPage() {
  const [logs, setLogs] = useState([]);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [pagination, setPagination] = useState({});

  useEffect(() => { fetchLogs(); }, [page]);

  const fetchLogs = async () => {
    try {
      setLoading(true);
      const res = await auditLogApi.list({ page, per_page: 20 });
      setLogs(res.data.data);
      setPagination(res.data.meta || {});
    } catch (err) { console.error(err); }
    finally { setLoading(false); }
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl lg:text-3xl font-bold text-surface-900 tracking-tight">Audit Log</h1>
        <p className="text-surface-500 mt-1">Complete history of all purchase order changes.</p>
      </div>

      <div className="bg-white rounded-2xl border border-surface-200/50">
        {loading ? <LoadingSpinner /> : logs.length === 0 ? (
          <EmptyState icon={History} title="No audit logs" description="Activity will appear here." />
        ) : (
          <div className="p-6 space-y-1">
            {logs.map((log, i) => (
              <div key={log.id} className="relative pl-8 pb-6 animate-slide-in" style={{animationDelay:`${i*30}ms`}}>
                {i < logs.length - 1 && <div className="absolute left-[11px] top-6 bottom-0 w-px bg-surface-200" />}
                <div className={`absolute left-0 top-1.5 w-[22px] h-[22px] rounded-full border-2 flex items-center justify-center
                  ${log.to_status === 'approved' ? 'border-emerald-500 bg-emerald-50' :
                    log.to_status === 'rejected' ? 'border-red-500 bg-red-50' :
                    'border-primary-500 bg-primary-50'}`}>
                  <div className={`w-2 h-2 rounded-full ${log.to_status === 'approved' ? 'bg-emerald-500' : log.to_status === 'rejected' ? 'bg-red-500' : 'bg-primary-500'}`} />
                </div>
                <div className="bg-surface-50 rounded-xl p-4 border border-surface-100 hover:border-surface-200 transition-colors">
                  <div className="flex flex-wrap items-center gap-2">
                    {log.from_status && <><StatusBadge status={log.from_status} /><span className="text-surface-400">→</span></>}
                    <StatusBadge status={log.to_status} />
                  </div>
                  {log.comment && <p className="mt-2 text-sm text-surface-700">{log.comment}</p>}
                  <div className="mt-2 flex flex-wrap items-center gap-3 text-xs text-surface-400">
                    <span className="flex items-center gap-1"><User className="w-3 h-3" />{log.actor?.name || 'System'}</span>
                    <span className="flex items-center gap-1"><Clock className="w-3 h-3" />{log.created_at_human}</span>
                    {log.metadata?.po_amount && <span>Amount: ${Number(log.metadata.po_amount).toLocaleString()}</span>}
                  </div>
                </div>
              </div>
            ))}
          </div>
        )}
        {pagination.last_page > 1 && (
          <div className="px-6 py-4 border-t border-surface-100 flex justify-center gap-2">
            <button onClick={() => setPage(p => Math.max(1, p-1))} disabled={page<=1}
              className="px-4 py-2 rounded-lg border border-surface-300 text-sm hover:bg-surface-50 disabled:opacity-50 transition">Previous</button>
            <span className="px-4 py-2 text-sm text-surface-500">Page {page} / {pagination.last_page}</span>
            <button onClick={() => setPage(p => p+1)} disabled={page>=pagination.last_page}
              className="px-4 py-2 rounded-lg border border-surface-300 text-sm hover:bg-surface-50 disabled:opacity-50 transition">Next</button>
          </div>
        )}
      </div>
    </div>
  );
}
