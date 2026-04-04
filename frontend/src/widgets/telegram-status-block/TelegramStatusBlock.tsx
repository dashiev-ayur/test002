import { useTelegramStatus } from '@/shared/api/hooks/useTelegramStatus'
import { getApiErrorMessage } from '@/shared/api/errors'

type Props = {
  shopId: number
}

function formatDate(iso: string | null): string {
  if (!iso) {
    return '—'
  }
  try {
    return new Intl.DateTimeFormat('ru-RU', {
      dateStyle: 'medium',
      timeStyle: 'short',
    }).format(new Date(iso))
  } catch {
    return iso
  }
}

export function TelegramStatusBlock({ shopId }: Props) {
  const { data, isLoading, isError, error } = useTelegramStatus(shopId)

  if (isLoading) {
    return (
      <section className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <p className="text-sm text-slate-500">Загрузка статуса…</p>
      </section>
    )
  }

  if (isError) {
    return (
      <section className="rounded-xl border border-red-200 bg-red-50 p-6 shadow-sm">
        <p className="text-sm text-red-800">{getApiErrorMessage(error)}</p>
      </section>
    )
  }

  if (!data) {
    return null
  }

  return (
    <section className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
      <h2 className="text-lg font-medium text-slate-800">Статус интеграции</h2>
      <dl className="mt-4 space-y-3 text-sm">
        <div className="flex justify-between gap-4">
          <dt className="text-slate-600">Включена</dt>
          <dd className="font-medium">{data.enabled ? 'Да' : 'Нет'}</dd>
        </div>
        <div className="flex justify-between gap-4">
          <dt className="text-slate-600">Chat ID</dt>
          <dd className="break-all font-mono text-xs text-slate-900">{data.chatId}</dd>
        </div>
        <div className="flex justify-between gap-4">
          <dt className="text-slate-600">Последняя успешная отправка</dt>
          <dd className="text-right">{formatDate(data.lastSentAt)}</dd>
        </div>
        <div className="flex justify-between gap-4">
          <dt className="text-slate-600">Успешных за 7 дней</dt>
          <dd className="font-medium">{data.sentCount}</dd>
        </div>
        <div className="flex justify-between gap-4">
          <dt className="text-slate-600">Ошибок за 7 дней</dt>
          <dd className="font-medium">{data.failedCount}</dd>
        </div>
      </dl>
    </section>
  )
}
