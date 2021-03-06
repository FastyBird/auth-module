<?php declare(strict_types = 1);

/**
 * AccountsModuleExtension.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:AccountsModule!
 * @subpackage     DI
 * @since          0.1.0
 *
 * @date           30.11.20
 */

namespace FastyBird\AccountsModule\DI;

use Contributte\Translation;
use Doctrine\Persistence;
use FastyBird\AccountsModule\Commands;
use FastyBird\AccountsModule\Controllers;
use FastyBird\AccountsModule\Entities;
use FastyBird\AccountsModule\Helpers;
use FastyBird\AccountsModule\Hydrators;
use FastyBird\AccountsModule\Middleware;
use FastyBird\AccountsModule\Models;
use FastyBird\AccountsModule\Router;
use FastyBird\AccountsModule\Schemas;
use FastyBird\AccountsModule\Security;
use FastyBird\AccountsModule\Subscribers;
use IPub\DoctrineCrud;
use Nette;
use Nette\DI;
use Nette\PhpGenerator;
use Nette\Schema;
use stdClass;

/**
 * Accounts module extension container
 *
 * @package        FastyBird:AccountsModule!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class AccountsModuleExtension extends DI\CompilerExtension implements Translation\DI\TranslationProviderInterface
{

	/**
	 * @param Nette\Configurator $config
	 * @param string $extensionName
	 *
	 * @return void
	 */
	public static function register(
		Nette\Configurator $config,
		string $extensionName = 'fbAccountsModule'
	): void {
		$config->onCompile[] = function (
			Nette\Configurator $config,
			DI\Compiler $compiler
		) use ($extensionName): void {
			$compiler->addExtension($extensionName, new AccountsModuleExtension());
		};
	}

	/**
	 * {@inheritdoc}
	 */
	public function getConfigSchema(): Schema\Schema
	{
		return Schema\Expect::structure([
			'apiPrefix' => Schema\Expect::bool(false),
		]);
	}

	/**
	 * {@inheritDoc}
	 */
	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		/** @var stdClass $configuration */
		$configuration = $this->getConfig();

		// Http router
		$builder->addDefinition($this->prefix('middleware.access'))
			->setType(Middleware\AccessMiddleware::class);

		$builder->addDefinition($this->prefix('middleware.urlFormat'))
			->setType(Middleware\UrlFormatMiddleware::class)
			->addTag('middleware', ['priority' => 150]);

		$builder->addDefinition($this->prefix('router.routes'))
			->setType(Router\Routes::class)
			->setArguments(['usePrefix' => $configuration->apiPrefix]);

		// Console commands
		$builder->addDefinition($this->prefix('commands.create'))
			->setType(Commands\Accounts\CreateCommand::class);

		$builder->addDefinition($this->prefix('commands.initialize'))
			->setType(Commands\InitializeCommand::class);

		// Database repositories
		$builder->addDefinition($this->prefix('models.accountRepository'))
			->setType(Models\Accounts\AccountRepository::class);

		$builder->addDefinition($this->prefix('models.emailRepository'))
			->setType(Models\Emails\EmailRepository::class);

		$builder->addDefinition($this->prefix('models.identityRepository'))
			->setType(Models\Identities\IdentityRepository::class);

		$builder->addDefinition($this->prefix('models.roleRepository'))
			->setType(Models\Roles\RoleRepository::class);

		// Database managers
		$builder->addDefinition($this->prefix('models.accountsManager'))
			->setType(Models\Accounts\AccountsManager::class)
			->setArgument('entityCrud', '__placeholder__');

		$builder->addDefinition($this->prefix('models.emailsManager'))
			->setType(Models\Emails\EmailsManager::class)
			->setArgument('entityCrud', '__placeholder__');

		$builder->addDefinition($this->prefix('models.identitiesManager'))
			->setType(Models\Identities\IdentitiesManager::class)
			->setArgument('entityCrud', '__placeholder__');

		$builder->addDefinition($this->prefix('models.rolesManager'))
			->setType(Models\Roles\RolesManager::class)
			->setArgument('entityCrud', '__placeholder__');

		// Events subscribers
		$builder->addDefinition($this->prefix('subscribers.entities'))
			->setType(Subscribers\EntitiesSubscriber::class);

		$builder->addDefinition($this->prefix('subscribers.accountEntity'))
			->setType(Subscribers\AccountEntitySubscriber::class);

		$builder->addDefinition($this->prefix('subscribers.emailEntity'))
			->setType(Subscribers\EmailEntitySubscriber::class);

		// API controllers
		$builder->addDefinition($this->prefix('controllers.session'))
			->setType(Controllers\SessionV1Controller::class)
			->addTag('nette.inject');

		$builder->addDefinition($this->prefix('controllers.account'))
			->setType(Controllers\AccountV1Controller::class)
			->addTag('nette.inject');

		$builder->addDefinition($this->prefix('controllers.accountEmails'))
			->setType(Controllers\AccountEmailsV1Controller::class)
			->addTag('nette.inject');

		$builder->addDefinition($this->prefix('controllers.accountIdentities'))
			->setType(Controllers\AccountIdentitiesV1Controller::class)
			->addTag('nette.inject');

		$builder->addDefinition($this->prefix('controllers.accounts'))
			->setType(Controllers\AccountsV1Controller::class)
			->addTag('nette.inject');

		$builder->addDefinition($this->prefix('controllers.emails'))
			->setType(Controllers\EmailsV1Controller::class)
			->addTag('nette.inject');

		$builder->addDefinition($this->prefix('controllers.identities'))
			->setType(Controllers\IdentitiesV1Controller::class)
			->addTag('nette.inject');

		$builder->addDefinition($this->prefix('controllers.roles'))
			->setType(Controllers\RolesV1Controller::class)
			->addTag('nette.inject');

		$builder->addDefinition($this->prefix('controllers.roleChildren'))
			->setType(Controllers\RoleChildrenV1Controller::class)
			->addTag('nette.inject');

		$builder->addDefinition($this->prefix('controllers.public'))
			->setType(Controllers\PublicV1Controller::class)
			->addTag('nette.inject');

		// API schemas
		$builder->addDefinition($this->prefix('schemas.account'))
			->setType(Schemas\Accounts\AccountSchema::class);

		$builder->addDefinition($this->prefix('schemas.email'))
			->setType(Schemas\Emails\EmailSchema::class);

		$builder->addDefinition($this->prefix('schemas.accountIdentity'))
			->setType(Schemas\Identities\IdentitySchema::class);

		$builder->addDefinition($this->prefix('schemas.role'))
			->setType(Schemas\Roles\RoleSchema::class);

		$builder->addDefinition($this->prefix('schemas.session'))
			->setType(Schemas\Sessions\SessionSchema::class);

		// API hydrators
		$builder->addDefinition($this->prefix('hydrators.accounts.profile'))
			->setType(Hydrators\Accounts\ProfileAccountHydrator::class);

		$builder->addDefinition($this->prefix('hydrators.accounts'))
			->setType(Hydrators\Accounts\AccountHydrator::class);

		$builder->addDefinition($this->prefix('hydrators.emails.profile'))
			->setType(Hydrators\Emails\ProfileEmailHydrator::class);

		$builder->addDefinition($this->prefix('hydrators.emails.email'))
			->setType(Hydrators\Emails\EmailHydrator::class);

		$builder->addDefinition($this->prefix('hydrators.identities.profile'))
			->setType(Hydrators\Identities\IdentityHydrator::class);

		$builder->addDefinition($this->prefix('hydrators.role'))
			->setType(Hydrators\Roles\RoleHydrator::class);

		// Security
		$builder->addDefinition($this->prefix('security.hash'))
			->setType(Helpers\SecurityHash::class);

		$builder->addDefinition($this->prefix('security.identityFactory'))
			->setType(Security\IdentityFactory::class);

		$builder->addDefinition($this->prefix('security.authenticator'))
			->setType(Security\Authenticator::class);

		// Nette services overwrite
		$builder->addDefinition('security.user')
			->setType(Security\User::class);
	}

	/**
	 * {@inheritDoc}
	 */
	public function beforeCompile(): void
	{
		parent::beforeCompile();

		$builder = $this->getContainerBuilder();

		/**
		 * Doctrine entities
		 */

		$ormAnnotationDriverService = $builder->getDefinition('nettrineOrmAnnotations.annotationDriver');

		if ($ormAnnotationDriverService instanceof DI\Definitions\ServiceDefinition) {
			$ormAnnotationDriverService->addSetup('addPaths', [[__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Entities']]);
		}

		$ormAnnotationDriverChainService = $builder->getDefinitionByType(Persistence\Mapping\Driver\MappingDriverChain::class);

		if ($ormAnnotationDriverChainService instanceof DI\Definitions\ServiceDefinition) {
			$ormAnnotationDriverChainService->addSetup('addDriver', [
				$ormAnnotationDriverService,
				'FastyBird\AccountsModule\Entities',
			]);
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function afterCompile(
		PhpGenerator\ClassType $class
	): void {
		$builder = $this->getContainerBuilder();

		$entityFactoryServiceName = $builder->getByType(DoctrineCrud\Crud\IEntityCrudFactory::class, true);

		$accountsManagerService = $class->getMethod('createService' . ucfirst($this->name) . '__models__accountsManager');
		$accountsManagerService->setBody('return new ' . Models\Accounts\AccountsManager::class . '($this->getService(\'' . $entityFactoryServiceName . '\')->create(\'' . Entities\Accounts\Account::class . '\'));');

		$emailsManagerService = $class->getMethod('createService' . ucfirst($this->name) . '__models__emailsManager');
		$emailsManagerService->setBody('return new ' . Models\Emails\EmailsManager::class . '($this->getService(\'' . $entityFactoryServiceName . '\')->create(\'' . Entities\Emails\Email::class . '\'));');

		$identitiesManagerService = $class->getMethod('createService' . ucfirst($this->name) . '__models__identitiesManager');
		$identitiesManagerService->setBody('return new ' . Models\Identities\IdentitiesManager::class . '($this->getService(\'' . $entityFactoryServiceName . '\')->create(\'' . Entities\Identities\Identity::class . '\'));');

		$rolesManagerService = $class->getMethod('createService' . ucfirst($this->name) . '__models__rolesManager');
		$rolesManagerService->setBody('return new ' . Models\Roles\RolesManager::class . '($this->getService(\'' . $entityFactoryServiceName . '\')->create(\'' . Entities\Roles\Role::class . '\'));');
	}

	/**
	 * @return string[]
	 */
	public function getTranslationResources(): array
	{
		return [
			__DIR__ . '/../Translations',
		];
	}

}
