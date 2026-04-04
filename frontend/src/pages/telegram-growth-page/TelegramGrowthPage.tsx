import { useParams } from 'react-router-dom'
import { ChatIdHint } from '@/widgets/chat-id-hint'
import { TelegramSettingsForm } from '@/widgets/telegram-settings-form'
import { TelegramStatusBlock } from '@/widgets/telegram-status-block'

export function TelegramGrowthPage() {
  const { shopId: shopIdParam } = useParams()
  const id = shopIdParam ? Number.parseInt(shopIdParam, 10) : Number.NaN

  if (!Number.isFinite(id) || id < 1 || !Number.isInteger(id)) {
    return (
      <div className="mx-auto min-h-screen max-w-lg bg-slate-50 p-6">
        <p className="text-red-700">Некорректный идентификатор магазина в адресе.</p>
      </div>
    )
  }

  return (
    <div className="min-h-screen bg-slate-50 text-slate-900">
      <div className="mx-auto max-w-lg space-y-8 px-4 py-8">
        <header>
          <h1 className="text-2xl font-semibold tracking-tight text-slate-900">
            Telegram для магазина #{id}
          </h1>
          <p className="mt-1 text-sm text-slate-600">Подключение уведомлений о новых заказах</p>
        </header>
        <TelegramSettingsForm shopId={id} />
        <TelegramStatusBlock shopId={id} />
        <ChatIdHint />
      </div>
    </div>
  )
}
