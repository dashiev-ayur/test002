import type { ButtonHTMLAttributes } from 'react'

type Props = ButtonHTMLAttributes<HTMLButtonElement> & {
  variant?: 'primary' | 'secondary'
  loading?: boolean
}

export function Button({
  variant = 'primary',
  loading = false,
  disabled,
  className = '',
  children,
  type = 'button',
  ...rest
}: Props) {
  const base =
    'inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-medium transition focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60'
  const variants = {
    primary: 'bg-slate-800 text-white hover:bg-slate-900 focus:ring-slate-500',
    secondary: 'border border-slate-300 bg-white text-slate-800 hover:bg-slate-50 focus:ring-slate-400',
  }

  return (
    <button
      type={type}
      className={`${base} ${variants[variant]} ${className}`}
      disabled={disabled === true || loading}
      {...rest}
    >
      {loading ? 'Сохранение…' : children}
    </button>
  )
}
