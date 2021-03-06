<?php declare(strict_types = 1);

/**
 * EmailsV1Controller.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:AccountsModule!
 * @subpackage     Controllers
 * @since          0.1.0
 *
 * @date           25.06.20
 */

namespace FastyBird\AccountsModule\Controllers;

use Doctrine;
use FastyBird\AccountsModule\Controllers;
use FastyBird\AccountsModule\Entities;
use FastyBird\AccountsModule\Exceptions;
use FastyBird\AccountsModule\Helpers;
use FastyBird\AccountsModule\Hydrators;
use FastyBird\AccountsModule\Models;
use FastyBird\AccountsModule\Queries;
use FastyBird\AccountsModule\Router;
use FastyBird\AccountsModule\Schemas;
use FastyBird\JsonApi\Exceptions as JsonApiExceptions;
use FastyBird\WebServer\Http as WebServerHttp;
use Fig\Http\Message\StatusCodeInterface;
use IPub\DoctrineCrud\Exceptions as DoctrineCrudExceptions;
use Nette\Utils;
use Psr\Http\Message;
use Throwable;

/**
 * Emails controller
 *
 * @package        FastyBird:AccountsModule!
 * @subpackage     Controllers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @Secured
 * @Secured\Role(manager,administrator)
 */
final class EmailsV1Controller extends BaseV1Controller
{

	use Controllers\Finders\TAccountFinder;
	use Controllers\Finders\TEmailFinder;

	/** @var Models\Emails\IEmailRepository */
	protected Models\Emails\IEmailRepository $emailRepository;

	/** @var Models\Accounts\IAccountRepository */
	protected Models\Accounts\IAccountRepository $accountRepository;

	/** @var Models\Emails\IEmailsManager */
	private Models\Emails\IEmailsManager $emailsManager;

	/** @var Hydrators\Emails\EmailHydrator */
	private Hydrators\Emails\EmailHydrator $emailHydrator;

	/** @var Helpers\SecurityHash */
	private Helpers\SecurityHash $securityHash;

	/** @var string */
	protected string $translationDomain = 'accounts-module.emails';

	public function __construct(
		Hydrators\Emails\EmailHydrator $emailHydrator,
		Models\Emails\IEmailRepository $emailRepository,
		Models\Emails\IEmailsManager $emailsManager,
		Models\Accounts\IAccountRepository $accountRepository,
		Helpers\SecurityHash $securityHash
	) {
		$this->emailHydrator = $emailHydrator;

		$this->emailRepository = $emailRepository;
		$this->emailsManager = $emailsManager;

		$this->accountRepository = $accountRepository;

		$this->securityHash = $securityHash;
	}

	/**
	 * @param Message\ServerRequestInterface $request
	 * @param WebServerHttp\Response $response
	 *
	 * @return WebServerHttp\Response
	 *
	 * @throws JsonApiExceptions\IJsonApiException
	 */
	public function index(
		Message\ServerRequestInterface $request,
		WebServerHttp\Response $response
	): WebServerHttp\Response {
		$findQuery = new Queries\FindEmailsQuery();
		$findQuery->forAccount($this->findAccount($request));

		$emails = $this->emailRepository->getResultSet($findQuery);

		return $response
			->withEntity(WebServerHttp\ScalarEntity::from($emails));
	}

	/**
	 * @param Message\ServerRequestInterface $request
	 * @param WebServerHttp\Response $response
	 *
	 * @return WebServerHttp\Response
	 *
	 * @throws JsonApiExceptions\IJsonApiException
	 */
	public function read(
		Message\ServerRequestInterface $request,
		WebServerHttp\Response $response
	): WebServerHttp\Response {
		// Find email
		$email = $this->findEmail($request, $this->findAccount($request));

		return $response
			->withEntity(WebServerHttp\ScalarEntity::from($email));
	}

	/**
	 * @param Message\ServerRequestInterface $request
	 * @param WebServerHttp\Response $response
	 *
	 * @return WebServerHttp\Response
	 *
	 * @throws JsonApiExceptions\IJsonApiException
	 * @throws Doctrine\DBAL\ConnectionException
	 */
	public function create(
		Message\ServerRequestInterface $request,
		WebServerHttp\Response $response
	): WebServerHttp\Response {
		// Get user profile account or url defined account
		$account = $this->findAccount($request);

		$document = $this->createDocument($request);

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			if ($document->getResource()->getType() === Schemas\Emails\EmailSchema::SCHEMA_TYPE) {
				$createData = $this->emailHydrator->hydrate($document);

				$this->validateAccountRelation($createData, $account);

				$createData->offsetSet('account', $account);
				$createData->offsetSet('verificationHash', $this->securityHash->createKey());
				$createData->offsetSet('verificationCreated', $this->dateFactory->getNow());

				// Store item into database
				$email = $this->emailsManager->create($createData);

			} else {
				throw new JsonApiExceptions\JsonApiErrorException(
					StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
					$this->translator->translate('//accounts-module.base.messages.invalidType.heading'),
					$this->translator->translate('//accounts-module.base.messages.invalidType.message'),
					[
						'pointer' => '/data/type',
					]
				);
			}

			// Commit all changes into database
			$this->getOrmConnection()
				->commit();

		} catch (Exceptions\EmailIsNotValidException $ex) {
			throw new JsonApiExceptions\JsonApiErrorException(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				$this->translator->translate('//accounts-module.base.messages.invalidAttribute.heading'),
				$this->translator->translate('//accounts-module.base.messages.invalidAttribute.message'),
				[
					'pointer' => '/data/attributes/address',
				]
			);

		} catch (Exceptions\EmailAlreadyTakenException $ex) {
			throw new JsonApiExceptions\JsonApiErrorException(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				$this->translator->translate('messages.taken.heading'),
				$this->translator->translate('messages.taken.message'),
				[
					'pointer' => '/data/attributes/address',
				]
			);

		} catch (DoctrineCrudExceptions\EntityCreationException $ex) {
			throw new JsonApiExceptions\JsonApiErrorException(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				$this->translator->translate('//accounts-module.base.messages.missingAttribute.heading'),
				$this->translator->translate('//accounts-module.base.messages.missingAttribute.message'),
				[
					'pointer' => 'data/attributes/' . $ex->getField(),
				]
			);

		} catch (JsonApiExceptions\IJsonApiException $ex) {
			throw $ex;

		} catch (Doctrine\DBAL\Exception\UniqueConstraintViolationException $ex) {
			if (preg_match("%PRIMARY'%", $ex->getMessage(), $match) === 1) {
				throw new JsonApiExceptions\JsonApiErrorException(
					StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
					$this->translator->translate('//accounts-module.base.messages.uniqueIdentifier.heading'),
					$this->translator->translate('//accounts-module.base.messages.uniqueIdentifier.message'),
					[
						'pointer' => '/data/id',
					]
				);

			} elseif (preg_match("%key '(?P<key>.+)_unique'%", $ex->getMessage(), $match) === 1) {
				$columnParts = explode('.', $match['key']);
				$columnKey = end($columnParts);

				if (is_string($columnKey) && Utils\Strings::startsWith($columnKey, 'email_')) {
					throw new JsonApiExceptions\JsonApiErrorException(
						StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
						$this->translator->translate('//accounts-module.base.messages.uniqueAttribute.heading'),
						$this->translator->translate('//accounts-module.base.messages.uniqueAttribute.message'),
						[
							'pointer' => '/data/attributes/' . Utils\Strings::substring($columnKey, 6),
						]
					);
				}
			}

			throw new JsonApiExceptions\JsonApiErrorException(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				$this->translator->translate('//accounts-module.base.messages.uniqueAttribute.heading'),
				$this->translator->translate('//accounts-module.base.messages.uniqueAttribute.message')
			);

		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error('[FB:AUTH_MODULE:CONTROLLER] ' . $ex->getMessage(), [
				'exception' => [
					'message' => $ex->getMessage(),
					'code'    => $ex->getCode(),
				],
			]);

			throw new JsonApiExceptions\JsonApiErrorException(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				$this->translator->translate('//accounts-module.base.messages.notCreated.heading'),
				$this->translator->translate('//accounts-module.base.messages.notCreated.message')
			);

		} finally {
			// Revert all changes when error occur
			if ($this->getOrmConnection()->isTransactionActive()) {
				$this->getOrmConnection()->rollBack();
			}
		}

		/** @var WebServerHttp\Response $response */
		$response = $response
			->withEntity(WebServerHttp\ScalarEntity::from($email))
			->withStatus(StatusCodeInterface::STATUS_CREATED);

		return $response;
	}

	/**
	 * @param Message\ServerRequestInterface $request
	 * @param WebServerHttp\Response $response
	 *
	 * @return WebServerHttp\Response
	 *
	 * @throws JsonApiExceptions\IJsonApiException
	 * @throws Doctrine\DBAL\ConnectionException
	 */
	public function update(
		Message\ServerRequestInterface $request,
		WebServerHttp\Response $response
	): WebServerHttp\Response {
		$document = $this->createDocument($request);

		$account = $this->findAccount($request);

		$email = $this->findEmail($request, $account);

		$this->validateIdentifier($request, $document);

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			if (
				$document->getResource()->getType() === Schemas\Emails\EmailSchema::SCHEMA_TYPE
				&& $email instanceof Entities\Emails\IEmail
			) {
				$updateData = $this->emailHydrator->hydrate($document, $email);

				$this->validateAccountRelation($updateData, $account);

				$email = $this->emailsManager->update($email, $updateData);

			} else {
				throw new JsonApiExceptions\JsonApiErrorException(
					StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
					$this->translator->translate('//accounts-module.base.messages.invalidType.heading'),
					$this->translator->translate('//accounts-module.base.messages.invalidType.message'),
					[
						'pointer' => '/data/type',
					]
				);
			}

			// Commit all changes into database
			$this->getOrmConnection()->commit();

		} catch (JsonApiExceptions\IJsonApiException $ex) {
			throw $ex;

		} catch (Exceptions\EmailHaveToBeDefaultException $ex) {
			throw new JsonApiExceptions\JsonApiErrorException(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				$this->translator->translate('//accounts-module.base.messages.invalidAttribute.heading'),
				$this->translator->translate('//accounts-module.base.messages.invalidAttribute.message'),
				[
					'pointer' => 'data/attributes/default',
				]
			);

		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error('[FB:AUTH_MODULE:CONTROLLER] ' . $ex->getMessage(), [
				'exception' => [
					'message' => $ex->getMessage(),
					'code'    => $ex->getCode(),
				],
			]);

			throw new JsonApiExceptions\JsonApiErrorException(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				$this->translator->translate('//accounts-module.base.messages.notUpdated.heading'),
				$this->translator->translate('//accounts-module.base.messages.notUpdated.message')
			);

		} finally {
			// Revert all changes when error occur
			if ($this->getOrmConnection()->isTransactionActive()) {
				$this->getOrmConnection()->rollBack();
			}
		}

		/** @var WebServerHttp\Response $response */
		$response = $response
			->withEntity(WebServerHttp\ScalarEntity::from($email));

		return $response;
	}

	/**
	 * @param Message\ServerRequestInterface $request
	 * @param WebServerHttp\Response $response
	 *
	 * @return WebServerHttp\Response
	 *
	 * @throws JsonApiExceptions\IJsonApiException
	 * @throws Doctrine\DBAL\ConnectionException
	 */
	public function delete(
		Message\ServerRequestInterface $request,
		WebServerHttp\Response $response
	): WebServerHttp\Response {
		$email = $this->findEmail($request, $this->findAccount($request));

		if ($email->isDefault()) {
			throw new JsonApiExceptions\JsonApiErrorException(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				$this->translator->translate('messages.defaultNotDeletable.heading'),
				$this->translator->translate('messages.defaultNotDeletable.message')
			);
		}

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$this->emailsManager->delete($email);

			// Commit all changes into database
			$this->getOrmConnection()->commit();

		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error('[FB:AUTH_MODULE:CONTROLLER] ' . $ex->getMessage(), [
				'exception' => [
					'message' => $ex->getMessage(),
					'code'    => $ex->getCode(),
				],
			]);

			throw new JsonApiExceptions\JsonApiErrorException(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				$this->translator->translate('//accounts-module.base.messages.notDeleted.heading'),
				$this->translator->translate('//accounts-module.base.messages.notDeleted.message')
			);

		} finally {
			// Revert all changes when error occur
			if ($this->getOrmConnection()->isTransactionActive()) {
				$this->getOrmConnection()->rollBack();
			}
		}

		/** @var WebServerHttp\Response $response */
		$response = $response
			->withStatus(StatusCodeInterface::STATUS_NO_CONTENT);

		return $response;
	}

	/**
	 * @param Message\ServerRequestInterface $request
	 * @param WebServerHttp\Response $response
	 *
	 * @return WebServerHttp\Response
	 *
	 * @throws JsonApiExceptions\IJsonApiException
	 */
	public function readRelationship(
		Message\ServerRequestInterface $request,
		WebServerHttp\Response $response
	): WebServerHttp\Response {
		// At first, try to load email
		$email = $this->findEmail($request, $this->findAccount($request));

		// & relation entity name
		$relationEntity = strtolower($request->getAttribute(Router\Routes::RELATION_ENTITY));

		if ($relationEntity === Schemas\Emails\EmailSchema::RELATIONSHIPS_ACCOUNT) {
			return $response
				->withEntity(WebServerHttp\ScalarEntity::from($email->getAccount()));
		}

		return parent::readRelationship($request, $response);
	}

}
