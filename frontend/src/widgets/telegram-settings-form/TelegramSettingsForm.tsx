import { zodResolver } from '@hookform/resolvers/zod'
import { useForm } from 'react-hook-form'
import {
  telegramFormSchema,
  type TelegramFormValues,
} from '@/entities/telegram'
import { ApiValidationError } from '@/shared/api/errors'
import { useTelegramConnectMutation } from '@/shared/api/hooks/useTelegramConnectMutation'
import { Button, Input } from '@/shared/ui'

type Props = {
  shopId: number
}

export function TelegramSettingsForm({ shopId }: Props) {
  const mutation = useTelegramConnectMutation(shopId)
  const {
    register,
    handleSubmit,
    setError,
    formState: { errors },
  } = useForm<TelegramFormValues>({
    resolver: zodResolver(telegramFormSchema),
    defaultValues: {
      botToken: '',
      chatId: '',
      enabled: true,
    },
  })

  const onSubmit = handleSubmit(async (values) => {
    try {
      await mutation.mutateAsync(values)
    } catch (e) {
      if (e instanceof ApiValidationError) {
        for (const [path, msgs] of Object.entries(e.fieldErrors)) {
          const key = path as keyof TelegramFormValues
          const first = msgs.at(0)
          if (first) {
            setError(key, { type: 'server', message: first })
          }
        }
      }
    }
  })

  return (
    <section className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
      <h2 className="text-lg font-medium text-slate-800">Настройки интеграции</h2>
      <form onSubmit={onSubmit} className="mt-4 space-y-4">
        <Input
          label="Токен бота"
          type="text"
          autoComplete="off"
          error={errors.botToken?.message}
          {...register('botToken')}
        />
        <Input
          label="Chat ID"
          type="text"
          autoComplete="off"
          error={errors.chatId?.message}
          {...register('chatId')}
        />
        <label className="flex cursor-pointer items-center gap-2 text-sm text-slate-800">
          <input
            type="checkbox"
            className="size-4 rounded border-slate-300 text-slate-800 focus:ring-slate-500"
            {...register('enabled')}
          />
          Интеграция включена
        </label>
        <Button type="submit" loading={mutation.isPending}>
          Сохранить
        </Button>
      </form>
    </section>
  )
}
