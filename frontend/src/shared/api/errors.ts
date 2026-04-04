import axios from 'axios'

export class ApiValidationError extends Error {
  readonly fieldErrors: Record<string, string[]>

  constructor(fieldErrors: Record<string, string[]>) {
    super('Ошибка валидации')
    this.name = 'ApiValidationError'
    this.fieldErrors = fieldErrors
  }
}

export function getApiErrorMessage(error: unknown): string {
  if (axios.isAxiosError(error)) {
    const data = error.response?.data as { error?: string } | undefined
    if (data?.error && typeof data.error === 'string') {
      return data.error
    }
    if (error.response?.status === 404) {
      return 'Магазин не найден.'
    }
    if (!error.response) {
      return 'Сеть недоступна. Проверьте соединение и URL API.'
    }
    return `Ошибка сервера (${error.response.status})`
  }
  if (error instanceof Error) {
    return error.message
  }
  return 'Неизвестная ошибка'
}
