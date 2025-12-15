import { ZiggyRoute } from 'ziggy-js'

declare global {
  function route(
    name?: string,
    params?: any,
    absolute?: boolean,
    config?: any
  ): ZiggyRoute
}

export {}
