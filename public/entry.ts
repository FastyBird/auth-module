import { ModuleOrigin } from '@fastybird/modules-metadata'
import { Plugin } from '@vuex-orm/core/dist/src/plugins/use'

import Account from '@/lib/models/accounts/Account'
import accounts from '@/lib/models/accounts'
import Email from '@/lib/models/emails/Email'
import emails from '@/lib/models/emails'
import Identity from '@/lib/models/identities/Identity'
import identities from '@/lib/models/identities'

// Import typing
import { ComponentsInterface, GlobalConfigInterface } from '@/types/accounts-module'

// install function executed by VuexORM.use()
const install: Plugin = function installVuexOrmWamp(components: ComponentsInterface, config: GlobalConfigInterface) {
  if (typeof config.originName !== 'undefined') {
    // @ts-ignore
    components.Model.prototype.$accountsModuleOrigin = config.originName
  } else {
    // @ts-ignore
    components.Model.prototype.$accountsModuleOrigin = ModuleOrigin.MODULE_ACCOUNTS_ORIGIN
  }

  config.database.register(Account, accounts)
  config.database.register(Email, emails)
  config.database.register(Identity, identities)
}

// Create module definition for VuexORM.use()
const plugin = {
  install,
}

// Default export is library as a whole, registered via VuexORM.use()
export default plugin

// Export model classes
export {
  Account,
  Email,
  Identity,
}

export * from '@/lib/errors'

// Re-export plugin typing
export * from '@/types/accounts-module'
