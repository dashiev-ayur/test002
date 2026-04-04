import { z } from 'zod'

export const telegramFormSchema = z.object({
  botToken: z.string().trim().min(1, 'Укажите токен бота'),
  chatId: z.string().trim().min(1, 'Укажите chat_id'),
  enabled: z.boolean(),
})

export type TelegramFormValues = z.infer<typeof telegramFormSchema>
