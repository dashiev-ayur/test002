export type TelegramIntegrationStatus = {
  enabled: boolean
  chatId: string
  lastSentAt: string | null
  sentCount: number
  failedCount: number
}

export type TelegramConnectResponse = {
  id: number
  enabled: boolean
  botToken: string
  chatId: string
  createdAt: string | null
  updatedAt: string | null
}

export type TelegramConnectInput = {
  botToken: string
  chatId: string
  enabled: boolean
}
