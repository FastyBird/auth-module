<?php declare(strict_types = 1);

/**
 * Constants.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:AccountsModule!
 * @subpackage     common
 * @since          0.1.0
 *
 * @date           12.06.20
 */

namespace FastyBird\AccountsModule;

use FastyBird\AccountsModule\Entities as AccountsModuleEntities;
use FastyBird\ModulesMetadata;
use FastyBird\SimpleAuth;

/**
 * Module constants
 *
 * @package        FastyBird:AccountsModule!
 * @subpackage     common
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Constants
{

	/**
	 * Module routing
	 */

	public const ROUTE_NAME_ME = 'me';
	public const ROUTE_NAME_ME_RELATIONSHIP = 'me.relationship';
	public const ROUTE_NAME_ME_EMAILS = 'me.emails';
	public const ROUTE_NAME_ME_EMAIL = 'me.email';
	public const ROUTE_NAME_ME_EMAIL_RELATIONSHIP = 'me.email.relationship';
	public const ROUTE_NAME_ME_IDENTITIES = 'me.identities';
	public const ROUTE_NAME_ME_IDENTITY = 'me.identity';
	public const ROUTE_NAME_ME_IDENTITY_RELATIONSHIP = 'me.identity.relationship';

	public const ROUTE_NAME_ACCOUNTS = 'accounts';
	public const ROUTE_NAME_ACCOUNT = 'account';
	public const ROUTE_NAME_ACCOUNT_RELATIONSHIP = 'account.relationship';
	public const ROUTE_NAME_ACCOUNT_EMAILS = 'account.emails';
	public const ROUTE_NAME_ACCOUNT_EMAIL = 'account.email';
	public const ROUTE_NAME_ACCOUNT_EMAIL_RELATIONSHIP = 'account.email.relationship';
	public const ROUTE_NAME_ACCOUNT_IDENTITIES = 'account.identities';
	public const ROUTE_NAME_ACCOUNT_IDENTITY = 'account.identity';
	public const ROUTE_NAME_ACCOUNT_IDENTITY_RELATIONSHIP = 'account.identity.relationship';

	public const ROUTE_NAME_SESSION = 'session';
	public const ROUTE_NAME_SESSION_RELATIONSHIP = 'session.relationship';

	public const ROUTE_NAME_ROLE = 'role';
	public const ROUTE_NAME_ROLES = 'roles';
	public const ROUTE_NAME_ROLE_RELATIONSHIP = 'role.relationship';
	public const ROUTE_NAME_ROLE_CHILDREN = 'role.children';

	/**
	 * Accounts default roles
	 */

	public const USER_ACCOUNT_DEFAULT_ROLES = [
		SimpleAuth\Constants::ROLE_USER,
	];

	public const MODULE_ACCOUNT_DEFAULT_ROLES = [
		SimpleAuth\Constants::ROLE_USER,
	];

	/**
	 * Account identities
	 */

	public const IDENTITY_UID_MAXIMAL_LENGTH = 50;
	public const IDENTITY_PASSWORD_MINIMAL_LENGTH = 8;

	/**
	 * Message bus routing keys mapping
	 */
	public const MESSAGE_BUS_CREATED_ENTITIES_ROUTING_KEYS_MAPPING = [
		AccountsModuleEntities\Accounts\Account::class    => ModulesMetadata\Constants::MESSAGE_BUS_ACCOUNTS_CREATED_ENTITY_ROUTING_KEY,
		AccountsModuleEntities\Emails\Email::class        => ModulesMetadata\Constants::MESSAGE_BUS_EMAILS_CREATED_ENTITY_ROUTING_KEY,
		AccountsModuleEntities\Identities\Identity::class => ModulesMetadata\Constants::MESSAGE_BUS_IDENTITIES_CREATED_ENTITY_ROUTING_KEY,
	];

	public const MESSAGE_BUS_UPDATED_ENTITIES_ROUTING_KEYS_MAPPING = [
		AccountsModuleEntities\Accounts\Account::class    => ModulesMetadata\Constants::MESSAGE_BUS_ACCOUNTS_UPDATED_ENTITY_ROUTING_KEY,
		AccountsModuleEntities\Emails\Email::class        => ModulesMetadata\Constants::MESSAGE_BUS_EMAILS_UPDATED_ENTITY_ROUTING_KEY,
		AccountsModuleEntities\Identities\Identity::class => ModulesMetadata\Constants::MESSAGE_BUS_IDENTITIES_UPDATED_ENTITY_ROUTING_KEY,
	];

	public const MESSAGE_BUS_DELETED_ENTITIES_ROUTING_KEYS_MAPPING = [
		AccountsModuleEntities\Accounts\Account::class    => ModulesMetadata\Constants::MESSAGE_BUS_ACCOUNTS_DELETED_ENTITY_ROUTING_KEY,
		AccountsModuleEntities\Emails\Email::class        => ModulesMetadata\Constants::MESSAGE_BUS_EMAILS_DELETED_ENTITY_ROUTING_KEY,
		AccountsModuleEntities\Identities\Identity::class => ModulesMetadata\Constants::MESSAGE_BUS_IDENTITIES_DELETED_ENTITY_ROUTING_KEY,
	];

}
