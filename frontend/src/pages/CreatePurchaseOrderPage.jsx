import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { purchaseOrderApi, lookupApi } from '../services/api';
import { Button } from '../components/UI';
import { Plus, Trash2, ArrowLeft, Send } from 'lucide-react';

export default function CreatePurchaseOrderPage() {
  const navigate = useNavigate();
  const [vendors, setVendors] = useState([]);
  const [loading, setLoading] = useState(false);
  const [errors, setErrors] = useState({});

  const [form, setForm] = useState({
    vendor_id: '',
    notes: '',
    items: [{ description: '', quantity: 1, unit_price: '', unit: 'pcs' }],
  });

  useEffect(() => {
    lookupApi.vendors().then(res => setVendors(res.data.data)).catch(console.error);
  }, []);

  const addItem = () => {
    setForm(f => ({
      ...f,
      items: [...f.items, { description: '', quantity: 1, unit_price: '', unit: 'pcs' }],
    }));
  };

  const removeItem = (index) => {
    if (form.items.length <= 1) return;
    setForm(f => ({
      ...f,
      items: f.items.filter((_, i) => i !== index),
    }));
  };

  const updateItem = (index, field, value) => {
    setForm(f => ({
      ...f,
      items: f.items.map((item, i) => i === index ? { ...item, [field]: value } : item),
    }));
  };

  const totalAmount = form.items.reduce((sum, item) => {
    return sum + (parseInt(item.quantity) || 0) * (parseFloat(item.unit_price) || 0);
  }, 0);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setErrors({});
    setLoading(true);

    try {
      const payload = {
        vendor_id: parseInt(form.vendor_id),
        notes: form.notes || null,
        items: form.items.map(item => ({
          description: item.description,
          quantity: parseInt(item.quantity),
          unit_price: parseFloat(item.unit_price),
          unit: item.unit || 'pcs',
        })),
      };

      const res = await purchaseOrderApi.create(payload);
      navigate(`/purchase-orders/${res.data.data.id}`);
    } catch (err) {
      if (err.response?.status === 422) {
        setErrors(err.response.data.errors || {});
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="max-w-4xl mx-auto space-y-6">
      {/* Header */}
      <div className="flex items-center gap-4">
        <button
          onClick={() => navigate(-1)}
          className="p-2 rounded-xl border border-surface-300 hover:bg-surface-50 transition"
        >
          <ArrowLeft className="w-5 h-5 text-surface-500" />
        </button>
        <div>
          <h1 className="text-2xl font-bold text-surface-900">Create Purchase Order</h1>
          <p className="text-sm text-surface-500 mt-0.5">Fill in the details to create a new PO</p>
        </div>
      </div>

      <form onSubmit={handleSubmit} className="space-y-6">
        {/* Basic Info */}
        <div className="bg-white rounded-2xl border border-surface-200/50 p-6 space-y-5">
          <h2 className="text-lg font-semibold text-surface-900">Basic Information</h2>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
              <label className="text-sm font-medium text-surface-700" htmlFor="vendor">Vendor *</label>
              <select
                id="vendor"
                value={form.vendor_id}
                onChange={(e) => setForm(f => ({ ...f, vendor_id: e.target.value }))}
                className="mt-1.5 w-full px-4 py-3 rounded-xl border border-surface-300 bg-surface-50 text-sm focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 outline-none transition-all"
                required
              >
                <option value="">Select a vendor</option>
                {vendors.map(v => (
                  <option key={v.id} value={v.id}>{v.name} ({v.code})</option>
                ))}
              </select>
              {errors.vendor_id && <p className="mt-1 text-xs text-red-500">{errors.vendor_id[0]}</p>}
            </div>

            <div>
              <label className="text-sm font-medium text-surface-700" htmlFor="total">Total Amount</label>
              <div className="mt-1.5 px-4 py-3 rounded-xl bg-surface-100 border border-surface-200 text-2xl font-bold text-surface-900">
                ${totalAmount.toLocaleString(undefined, { minimumFractionDigits: 2 })}
              </div>
            </div>
          </div>

          <div>
            <label className="text-sm font-medium text-surface-700" htmlFor="notes">Notes</label>
            <textarea
              id="notes"
              value={form.notes}
              onChange={(e) => setForm(f => ({ ...f, notes: e.target.value }))}
              rows={3}
              className="mt-1.5 w-full px-4 py-3 rounded-xl border border-surface-300 bg-surface-50 text-sm focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 outline-none transition-all resize-none"
              placeholder="Optional notes for this purchase order"
            />
          </div>
        </div>

        {/* Line Items */}
        <div className="bg-white rounded-2xl border border-surface-200/50 p-6 space-y-4">
          <div className="flex items-center justify-between">
            <h2 className="text-lg font-semibold text-surface-900">Line Items</h2>
            <Button type="button" variant="secondary" size="sm" onClick={addItem}>
              <Plus className="w-4 h-4" /> Add Item
            </Button>
          </div>
          {errors.items && <p className="text-xs text-red-500">{errors.items[0]}</p>}

          <div className="space-y-3">
            {form.items.map((item, index) => (
              <div key={index} className="flex gap-3 items-start p-4 rounded-xl bg-surface-50 border border-surface-200 animate-fade-in">
                <div className="flex-1 grid grid-cols-1 md:grid-cols-12 gap-3">
                  <div className="md:col-span-5">
                    <label className="text-xs font-medium text-surface-500">Description *</label>
                    <input
                      type="text"
                      value={item.description}
                      onChange={(e) => updateItem(index, 'description', e.target.value)}
                      className="mt-1 w-full px-3 py-2 rounded-lg border border-surface-300 text-sm focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 outline-none transition-all"
                      placeholder="Item description"
                      required
                    />
                  </div>
                  <div className="md:col-span-2">
                    <label className="text-xs font-medium text-surface-500">Qty *</label>
                    <input
                      type="number"
                      min="1"
                      value={item.quantity}
                      onChange={(e) => updateItem(index, 'quantity', e.target.value)}
                      className="mt-1 w-full px-3 py-2 rounded-lg border border-surface-300 text-sm focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 outline-none transition-all"
                      required
                    />
                  </div>
                  <div className="md:col-span-2">
                    <label className="text-xs font-medium text-surface-500">Unit Price *</label>
                    <input
                      type="number"
                      min="0.01"
                      step="0.01"
                      value={item.unit_price}
                      onChange={(e) => updateItem(index, 'unit_price', e.target.value)}
                      className="mt-1 w-full px-3 py-2 rounded-lg border border-surface-300 text-sm focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 outline-none transition-all"
                      placeholder="0.00"
                      required
                    />
                  </div>
                  <div className="md:col-span-2">
                    <label className="text-xs font-medium text-surface-500">Unit</label>
                    <input
                      type="text"
                      value={item.unit}
                      onChange={(e) => updateItem(index, 'unit', e.target.value)}
                      className="mt-1 w-full px-3 py-2 rounded-lg border border-surface-300 text-sm focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 outline-none transition-all"
                    />
                  </div>
                  <div className="md:col-span-1 flex items-end">
                    <p className="text-sm font-semibold text-surface-700 pb-2">
                      ${((parseInt(item.quantity) || 0) * (parseFloat(item.unit_price) || 0)).toLocaleString(undefined, { minimumFractionDigits: 2 })}
                    </p>
                  </div>
                </div>
                <button
                  type="button"
                  onClick={() => removeItem(index)}
                  disabled={form.items.length <= 1}
                  className="p-2 rounded-lg text-surface-400 hover:text-red-500 hover:bg-red-50 transition-colors disabled:opacity-30 mt-6"
                >
                  <Trash2 className="w-4 h-4" />
                </button>
              </div>
            ))}
          </div>

          {/* Total */}
          <div className="flex justify-end pt-4 border-t border-surface-200">
            <div className="text-right">
              <p className="text-sm text-surface-500">Grand Total</p>
              <p className="text-3xl font-bold text-surface-900">
                ${totalAmount.toLocaleString(undefined, { minimumFractionDigits: 2 })}
              </p>
            </div>
          </div>
        </div>

        {/* Actions */}
        <div className="flex justify-end gap-3 pt-2">
          <Button type="button" variant="secondary" onClick={() => navigate(-1)}>
            Cancel
          </Button>
          <Button type="submit" loading={loading}>
            <Send className="w-4 h-4" /> Create Purchase Order
          </Button>
        </div>
      </form>
    </div>
  );
}
