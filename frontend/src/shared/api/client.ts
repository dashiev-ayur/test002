import axios from 'axios'

const rawBase = import.meta.env.VITE_API_BASE_URL ?? ''
const baseURL = typeof rawBase === 'string' ? rawBase.replace(/\/$/, '') : ''

export const api = axios.create({
  baseURL,
  headers: {
    'Content-Type': 'application/json',
  },
})
