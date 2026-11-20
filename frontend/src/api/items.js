import { api, qs } from './client'

export const itemsApi = {
  list: (params) => api.get(`/api/items${qs(params)}`),
  get: (cod) => api.get(`/api/items/detail${qs({ cod })}`),
  create: (body) => api.post('/api/items', body),
  update: (cod, body) => api.put(`/api/items/detail${qs({ cod })}`, body),
  remove: (cod) => api.delete(`/api/items/detail${qs({ cod })}`),
  movements: (cod, params = {}) => api.get(`/api/items/movimientos${qs({ cod, ...params })}`),
}
