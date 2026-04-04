import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom'
import { ToastContainer } from 'react-toastify'
import 'react-toastify/dist/ReactToastify.css'
import { TelegramGrowthPage } from '@/pages/telegram-growth-page'

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      retry: 1,
      refetchOnWindowFocus: false,
    },
  },
})

export function App() {
  return (
    <QueryClientProvider client={queryClient}>
      <BrowserRouter>
        <Routes>
          <Route path="/" element={<Navigate to="/shops/1/growth/telegram" replace />} />
          <Route path="/shops/:shopId/growth/telegram" element={<TelegramGrowthPage />} />
          <Route path="*" element={<Navigate to="/shops/1/growth/telegram" replace />} />
        </Routes>
        <ToastContainer position="top-right" autoClose={4000} />
      </BrowserRouter>
    </QueryClientProvider>
  )
}
