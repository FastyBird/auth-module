import { Database, Model } from '@vuex-orm/core'

export interface GlobalConfigInterface {
  database: Database
  originName?: string
}

export interface ComponentsInterface {
  Model: typeof Model
}

declare module '@vuex-orm/core' {
  // eslint-disable-next-line @typescript-eslint/no-namespace
  namespace Model {
    // Exchange origin name
    const $accountsModuleOrigin: string
  }
}

// Re-export models types
export * from '@/lib/types'
export * from '@/lib/models/accounts/types'
export * from '@/lib/models/emails/types'
export * from '@/lib/models/identities/types'
