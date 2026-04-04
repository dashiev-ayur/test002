import { useQuery } from '@tanstack/react-query'
import { fetchTelegramStatus } from '@/shared/api/telegram-api'

export const telegramStatusQueryKey = (shopId: number) => ['telegram', 'status', shopId] as const

export function useTelegramStatus(shopId: number) {
  return useQuery({
    queryKey: telegramStatusQueryKey(shopId),
    queryFn: () => fetchTelegramStatus(shopId),
  })
}
