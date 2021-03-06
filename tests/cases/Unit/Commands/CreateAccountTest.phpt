<?php declare(strict_types = 1);

namespace Tests\Cases;

use Contributte\Translation;
use Doctrine\Persistence;
use FastyBird\AccountsModule\Commands;
use FastyBird\AccountsModule\Entities;
use FastyBird\AccountsModule\Helpers;
use FastyBird\AccountsModule\Models;
use FastyBird\AccountsModule\Queries;
use FastyBird\SimpleAuth;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Tester\Assert;

require_once __DIR__ . '/../../../bootstrap.php';
require_once __DIR__ . '/../DbTestCase.php';

/**
 * @testCase
 */
final class CreateAccountTest extends DbTestCase
{

	public function testExecute(): void
	{
		/** @var Models\Accounts\IAccountsManager $accountsManager */
		$accountsManager = $this->getContainer()
			->getByType(Models\Accounts\AccountsManager::class);

		/** @var Models\Emails\IEmailRepository $emailRepository */
		$emailRepository = $this->getContainer()
			->getByType(Models\Emails\EmailRepository::class);

		/** @var Models\Emails\IEmailsManager $emailsManager */
		$emailsManager = $this->getContainer()
			->getByType(Models\Emails\EmailsManager::class);

		/** @var Models\Identities\IIdentitiesManager $identitiesManager */
		$identitiesManager = $this->getContainer()
			->getByType(Models\Identities\IdentitiesManager::class);

		/** @var Models\Roles\IRoleRepository $roleRepository */
		$roleRepository = $this->getContainer()
			->getByType(Models\Roles\RoleRepository::class);

		/** @var Models\Emails\IEmailRepository $emailRepository */
		$emailRepository = $this->getContainer()
			->getByType(Models\Emails\EmailRepository::class);

		/** @var Models\Identities\IIdentityRepository $identityRepository */
		$identityRepository = $this->getContainer()
			->getByType(Models\Identities\IdentityRepository::class);

		/** @var Translation\Translator $translator */
		$translator = $this->getContainer()
			->getByType(Translation\Translator::class);

		/** @var Persistence\ManagerRegistry $managerRegistry */
		$managerRegistry = $this->getContainer()
			->getByType(Persistence\ManagerRegistry::class);

		$application = new Application();
		$application->add(new Commands\Accounts\CreateCommand(
			$accountsManager,
			$emailRepository,
			$emailsManager,
			$identitiesManager,
			$roleRepository,
			$translator,
			$managerRegistry,
		));

		$command = $application->get('fb:accounts-module:create:account');

		$commandTester = new CommandTester($command);
		$result = $commandTester->execute([
			'lastName'  => 'Balboa',
			'firstName' => 'Rocky',
			'email'     => 'rocky@balboa.com',
			'password'  => 'someRandomPassword',
			'role'      => SimpleAuth\Constants::ROLE_USER,
		]);

		Assert::same(0, $result);

		$findEmail = new Queries\FindEmailsQuery();
		$findEmail->byAddress('rocky@balboa.com');

		$email = $emailRepository->findOneBy($findEmail);

		Assert::type(Entities\Emails\Email::class, $email);
		Assert::same('Balboa Rocky', $email->getAccount()
			->getName());

		$findIdentity = new Queries\FindIdentitiesQuery();
		$findIdentity->byUid('rocky@balboa.com');

		$identity = $identityRepository->findOneBy($findIdentity);

		Assert::type(Entities\Identities\IIdentity::class, $identity);

		if ($identity instanceof Entities\Identities\IIdentity) {
			$password = new Helpers\Password(
				null,
				'someRandomPassword',
				$identity->getSalt()
			);

			Assert::same($password->getHash(), $identity->getPassword()
				->getHash());
		}
	}

}

$test_case = new CreateAccountTest();
$test_case->run();
