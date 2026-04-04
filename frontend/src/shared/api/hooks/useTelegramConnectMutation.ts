import { useMutation, useQueryClient } from '@tanstack/react-query'
import { toast } from 'react-toastify'
import type { TelegramConnectInput } from '@/entities/telegram'
import { ApiValidationError, getApiErrorMessage } from '@/shared/api/errors'
import { postTelegramConnect } from '@/shared/api/telegram-api'
import { telegramStatusQueryKey } from '@/shared/api/hooks/useTelegramStatus'

export function useTelegramConnectMutation(shopId: number) {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: (body: TelegramConnectInput) => postTelegramConnect(shopId, body),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: telegramStatusQueryKey(shopId) })
      toast.success('Настройки сохранены')
    },
    onError: (error: unknown) => {
      if (error instanceof ApiValidationError) {
        return
      }
      toast.error(getApiErrorMessage(error))
    },
  })
}
