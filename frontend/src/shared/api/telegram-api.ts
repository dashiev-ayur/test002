import axios from 'axios'
import type {
  TelegramConnectInput,
  TelegramConnectResponse,
  TelegramIntegrationStatus,
} from '@/entities/telegram'
import { api } from './client'
import { ApiValidationError } from './errors'

export async function fetchTelegramStatus(shopId: number): Promise<TelegramIntegrationStatus> {
  const { data } = await api.get<TelegramIntegrationStatus>(`/shops/${shopId}/telegram/status`)
  return data
}

export async function postTelegramConnect(
  shopId: number,
  body: TelegramConnectInput,
): Promise<TelegramConnectResponse> {
  try {
    console.log('body>>>>', shopId,body);
    const { data } = await api.post<TelegramConnectResponse>(
      `/shops/${shopId}/telegram/connect`,
      body,
    )
    return data
  } catch (e) {
    if (axios.isAxiosError(e) && e.response?.status === 422) {
      const payload = e.response.data as { errors?: Record<string, string[]>; error?: string }
      if (payload.errors && typeof payload.errors === 'object') {
        throw new ApiValidationError(payload.errors)
      }
    }
    throw e
  }
}
