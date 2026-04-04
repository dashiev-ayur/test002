export function ChatIdHint() {
  return (
    <aside className="rounded-xl border border-dashed border-slate-300 bg-slate-100/80 p-4 text-sm text-slate-700">
      <p className="font-medium text-slate-800">Как узнать chat_id</p>
      <p className="mt-2 leading-relaxed">
        Напишите своему боту в Telegram любое сообщение, затем откройте в браузере{' '}
        <code className="rounded bg-white px-1 py-0.5 text-xs text-slate-900">
          https://api.telegram.org/bot&lt;ваш_токен&gt;/getUpdates
        </code>{' '}
        и найдите число в поле <code className="rounded bg-white px-1 text-xs">chat → id</code> для
        личного чата, или используйте бота вроде @userinfobot — он пришлёт ваш id в ответ.
      </p>
    </aside>
  )
}
