export function StatusBadge({ status }) {
  const config = {
    draft: { label: 'Draft', classes: 'bg-surface-100 text-surface-600 ring-surface-200' },
    pending_manager: { label: 'Pending Manager', classes: 'bg-amber-50 text-amber-700 ring-amber-200' },
    pending_director: { label: 'Pending Director', classes: 'bg-orange-50 text-orange-700 ring-orange-200' },
    pending_finance: { label: 'Pending Finance', classes: 'bg-blue-50 text-blue-700 ring-blue-200' },
    approved: { label: 'Approved', classes: 'bg-emerald-50 text-emerald-700 ring-emerald-200' },
    rejected: { label: 'Rejected', classes: 'bg-red-50 text-red-700 ring-red-200' },
    cancelled: { label: 'Cancelled', classes: 'bg-surface-100 text-surface-500 ring-surface-200' },
  };

  const { label, classes } = config[status] || { label: status, classes: 'bg-surface-100 text-surface-600' };

  return (
    <span className={`inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold ring-1 ring-inset ${classes}`}>
      {label}
    </span>
  );
}

export function StatCard({ title, value, subtitle, icon: Icon, color = 'primary', trend }) {
  const colorMap = {
    primary: 'from-primary-500 to-primary-700',
    success: 'from-emerald-500 to-emerald-700',
    warning: 'from-amber-500 to-amber-700',
    danger: 'from-red-500 to-red-700',
    info: 'from-blue-500 to-blue-700',
    purple: 'from-purple-500 to-purple-700',
  };

  return (
    <div className="bg-white rounded-2xl border border-surface-200/50 p-6 hover:shadow-lg transition-all duration-300 group">
      <div className="flex items-start justify-between">
        <div>
          <p className="text-sm font-medium text-surface-500">{title}</p>
          <p className="mt-2 text-3xl font-bold text-surface-900 tracking-tight">{value}</p>
          {subtitle && <p className="mt-1 text-sm text-surface-500">{subtitle}</p>}
        </div>
        <div className={`w-12 h-12 rounded-xl bg-gradient-to-br ${colorMap[color]} flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform duration-300`}>
          {Icon && <Icon className="w-6 h-6 text-white" />}
        </div>
      </div>
    </div>
  );
}

export function EmptyState({ icon: Icon, title, description, action }) {
  return (
    <div className="flex flex-col items-center justify-center py-16 text-center">
      {Icon && (
        <div className="w-16 h-16 rounded-2xl bg-surface-100 flex items-center justify-center mb-4">
          <Icon className="w-8 h-8 text-surface-400" />
        </div>
      )}
      <h3 className="text-lg font-semibold text-surface-900">{title}</h3>
      {description && <p className="mt-2 text-sm text-surface-500 max-w-sm">{description}</p>}
      {action && <div className="mt-6">{action}</div>}
    </div>
  );
}

export function LoadingSpinner({ size = 'md' }) {
  const sizes = { sm: 'w-4 h-4', md: 'w-8 h-8', lg: 'w-12 h-12' };
  return (
    <div className="flex items-center justify-center py-12">
      <div className={`${sizes[size]} border-3 border-surface-200 border-t-primary-500 rounded-full animate-spin`} />
    </div>
  );
}

export function Button({ children, variant = 'primary', size = 'md', loading, disabled, className = '', ...props }) {
  const variants = {
    primary: 'bg-gradient-to-r from-primary-600 to-primary-700 text-white hover:from-primary-700 hover:to-primary-800 shadow-lg shadow-primary-500/20 hover:shadow-primary-500/30',
    secondary: 'bg-white text-surface-700 border border-surface-300 hover:bg-surface-50 shadow-sm',
    success: 'bg-gradient-to-r from-emerald-600 to-emerald-700 text-white hover:from-emerald-700 hover:to-emerald-800 shadow-lg shadow-emerald-500/20',
    danger: 'bg-gradient-to-r from-red-600 to-red-700 text-white hover:from-red-700 hover:to-red-800 shadow-lg shadow-red-500/20',
    ghost: 'text-surface-600 hover:bg-surface-100',
  };
  const sizes = {
    sm: 'px-3 py-1.5 text-xs',
    md: 'px-4 py-2.5 text-sm',
    lg: 'px-6 py-3 text-base',
  };

  return (
    <button
      className={`
        inline-flex items-center justify-center gap-2 font-semibold rounded-xl
        transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed
        ${variants[variant]} ${sizes[size]} ${className}
      `}
      disabled={disabled || loading}
      {...props}
    >
      {loading && <div className="w-4 h-4 border-2 border-current/30 border-t-current rounded-full animate-spin" />}
      {children}
    </button>
  );
}
