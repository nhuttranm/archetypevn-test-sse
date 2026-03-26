import { useState, useEffect } from 'react';
import { approvalRuleApi } from '../services/api';
import { Button, LoadingSpinner, EmptyState } from '../components/UI';
import { Settings, Plus, Trash2, Edit2, Save, X, ArrowRight } from 'lucide-react';

const STATES = ['draft','pending_manager','pending_director','pending_finance','approved'];
const ROLES = ['staff','manager','director','finance'];
const STATE_LABELS = { draft:'Draft', pending_manager:'Pending Manager', pending_director:'Pending Director', pending_finance:'Pending Finance', approved:'Approved' };

export default function WorkflowConfigPage() {
  const [rules, setRules] = useState([]);
  const [loading, setLoading] = useState(true);
  const [editingId, setEditingId] = useState(null);
  const [showCreate, setShowCreate] = useState(false);
  const [form, setForm] = useState({ name:'', current_state:'draft', next_state:'pending_manager', required_role:'staff', condition_expression:{}, priority:0, is_active:true });

  useEffect(() => { fetchRules(); }, []);

  const fetchRules = async () => {
    try { setLoading(true); const res = await approvalRuleApi.list(); setRules(res.data.data); }
    catch(e) { console.error(e); } finally { setLoading(false); }
  };

  const handleCreate = async () => {
    try { await approvalRuleApi.create(form); setShowCreate(false); resetForm(); fetchRules(); }
    catch(e) { alert('Failed to create rule'); }
  };

  const handleUpdate = async (id) => {
    try { await approvalRuleApi.update(id, form); setEditingId(null); resetForm(); fetchRules(); }
    catch(e) { alert('Failed to update rule'); }
  };

  const handleDelete = async (id) => {
    if(!confirm('Delete this rule?')) return;
    try { await approvalRuleApi.delete(id); fetchRules(); }
    catch(e) { alert('Failed to delete rule'); }
  };

  const startEdit = (rule) => {
    setEditingId(rule.id);
    setForm({ name: rule.name||'', current_state: rule.current_state, next_state: rule.next_state, required_role: rule.required_role,
      condition_expression: rule.condition_expression||{}, priority: rule.priority||0, is_active: rule.is_active });
  };

  const resetForm = () => setForm({ name:'', current_state:'draft', next_state:'pending_manager', required_role:'staff', condition_expression:{}, priority:0, is_active:true });

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl lg:text-3xl font-bold text-surface-900 tracking-tight">Workflow Configuration</h1>
          <p className="text-surface-500 mt-1">Manage dynamic approval rules for purchase orders.</p>
        </div>
        <Button onClick={() => { resetForm(); setShowCreate(true); }}><Plus className="w-4 h-4" />Add Rule</Button>
      </div>

      {/* Create Form */}
      {showCreate && (
        <div className="bg-white rounded-2xl border-2 border-primary-200 p-6 animate-fade-in">
          <h3 className="text-lg font-semibold text-surface-900 mb-4">New Approval Rule</h3>
          <RuleForm form={form} setForm={setForm} />
          <div className="flex gap-3 mt-4">
            <Button onClick={handleCreate}><Save className="w-4 h-4"/>Save</Button>
            <Button variant="secondary" onClick={() => setShowCreate(false)}><X className="w-4 h-4"/>Cancel</Button>
          </div>
        </div>
      )}

      {/* Rules List */}
      {loading ? <LoadingSpinner /> : rules.length === 0 ? (
        <EmptyState icon={Settings} title="No approval rules" description="Add rules to configure the approval workflow." />
      ) : (
        <div className="space-y-3">
          {rules.map(rule => (
            <div key={rule.id} className="bg-white rounded-2xl border border-surface-200/50 p-5 hover:shadow-sm transition-all">
              {editingId === rule.id ? (
                <div>
                  <RuleForm form={form} setForm={setForm} />
                  <div className="flex gap-3 mt-4">
                    <Button size="sm" onClick={() => handleUpdate(rule.id)}><Save className="w-3.5 h-3.5"/>Save</Button>
                    <Button size="sm" variant="secondary" onClick={() => setEditingId(null)}><X className="w-3.5 h-3.5"/>Cancel</Button>
                  </div>
                </div>
              ) : (
                <div className="flex items-center gap-4">
                  <div className="flex-1">
                    <p className="text-sm font-semibold text-surface-900">{rule.name || 'Unnamed Rule'}</p>
                    <div className="flex items-center gap-2 mt-2 text-sm">
                      <span className="px-2.5 py-1 rounded-lg bg-surface-100 text-surface-600 font-medium">{STATE_LABELS[rule.current_state]}</span>
                      <ArrowRight className="w-4 h-4 text-surface-400" />
                      <span className="px-2.5 py-1 rounded-lg bg-primary-50 text-primary-700 font-medium">{STATE_LABELS[rule.next_state]}</span>
                      <span className="px-2.5 py-1 rounded-lg bg-amber-50 text-amber-700 font-medium capitalize">Role: {rule.required_role}</span>
                      {!rule.is_active && <span className="px-2 py-0.5 rounded bg-red-100 text-red-600 text-xs">Inactive</span>}
                    </div>
                    {rule.condition_expression && Object.keys(rule.condition_expression).length > 0 && (
                      <p className="mt-1.5 text-xs text-surface-400 font-mono">Conditions: {JSON.stringify(rule.condition_expression)}</p>
                    )}
                  </div>
                  <div className="flex gap-2">
                    <button onClick={() => startEdit(rule)} className="p-2 rounded-lg hover:bg-surface-100 text-surface-400 hover:text-primary-600 transition"><Edit2 className="w-4 h-4"/></button>
                    <button onClick={() => handleDelete(rule.id)} className="p-2 rounded-lg hover:bg-red-50 text-surface-400 hover:text-red-600 transition"><Trash2 className="w-4 h-4"/></button>
                  </div>
                </div>
              )}
            </div>
          ))}
        </div>
      )}
    </div>
  );
}

function RuleForm({ form, setForm }) {
  return (
    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div>
        <label className="text-xs font-medium text-surface-500">Name</label>
        <input type="text" value={form.name} onChange={e => setForm(f=>({...f,name:e.target.value}))}
          className="mt-1 w-full px-3 py-2 rounded-lg border border-surface-300 text-sm focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 outline-none" placeholder="Rule name" />
      </div>
      <div>
        <label className="text-xs font-medium text-surface-500">From State</label>
        <select value={form.current_state} onChange={e => setForm(f=>({...f,current_state:e.target.value}))}
          className="mt-1 w-full px-3 py-2 rounded-lg border border-surface-300 text-sm focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 outline-none">
          {STATES.filter(s=>s!=='approved').map(s=><option key={s} value={s}>{STATE_LABELS[s]}</option>)}
        </select>
      </div>
      <div>
        <label className="text-xs font-medium text-surface-500">To State</label>
        <select value={form.next_state} onChange={e => setForm(f=>({...f,next_state:e.target.value}))}
          className="mt-1 w-full px-3 py-2 rounded-lg border border-surface-300 text-sm focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 outline-none">
          {STATES.filter(s=>s!=='draft').map(s=><option key={s} value={s}>{STATE_LABELS[s]}</option>)}
        </select>
      </div>
      <div>
        <label className="text-xs font-medium text-surface-500">Required Role</label>
        <select value={form.required_role} onChange={e => setForm(f=>({...f,required_role:e.target.value}))}
          className="mt-1 w-full px-3 py-2 rounded-lg border border-surface-300 text-sm focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 outline-none">
          {ROLES.map(r=><option key={r} value={r} className="capitalize">{r}</option>)}
        </select>
      </div>
      <div>
        <label className="text-xs font-medium text-surface-500">Priority</label>
        <input type="number" min="0" value={form.priority} onChange={e => setForm(f=>({...f,priority:parseInt(e.target.value)||0}))}
          className="mt-1 w-full px-3 py-2 rounded-lg border border-surface-300 text-sm focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 outline-none" />
      </div>
      <div className="flex items-end">
        <label className="flex items-center gap-2 text-sm">
          <input type="checkbox" checked={form.is_active} onChange={e => setForm(f=>({...f,is_active:e.target.checked}))} className="rounded" />
          Active
        </label>
      </div>
    </div>
  );
}
