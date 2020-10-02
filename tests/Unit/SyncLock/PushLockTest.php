<?php declare(strict_types = 1);

namespace CITests\Unit\SyncLock;

include __DIR__ . '/../../bootstrap.php';

/**
 * @testCase
 */
class PushLockTest extends \Tester\TestCase
{

	private const LOCK_FILE = 'push.lock';

	public function testLockingPush(): void
	{
		$lockPush = new \CI\SyncLock\PushLock(
			self::LOCK_FILE,
			new \DateInterval("PT5M")
		);

		\Tester\Assert::true($lockPush->checkAndLock(new \DateTimeImmutable()));
		\Tester\Assert::true($lockPush->isLocked());
	}


	public function testReleasingLock(): void
	{
		$lockPush = new \CI\SyncLock\PushLock(
			self::LOCK_FILE,
			new \DateInterval("PT5M")
		);

		\Tester\Assert::true($lockPush->checkAndLock(new \DateTimeImmutable()));
		\Tester\Assert::true($lockPush->isLocked());
		\Tester\Assert::true($lockPush->releaseLock());
		\Tester\Assert::false($lockPush->isLocked());
	}


	public function testRaceCondition(): void
	{
		$lockPush = new \CI\SyncLock\PushLock(
			self::LOCK_FILE,
			new \DateInterval("PT5M")
		);

		$otherProcessLock = new \CI\SyncLock\PushLock(
			self::LOCK_FILE,
			new \DateInterval("PT5M")
		);

		\Tester\Assert::true($lockPush->checkAndLock(new \DateTimeImmutable()));
		\Tester\Assert::false($otherProcessLock->checkAndLock(new \DateTimeImmutable()));
	}


	public function testLockExpiration(): void
	{
		$lockPush = new \CI\SyncLock\PushLock(
			self::LOCK_FILE,
			new \DateInterval("PT5M")
		);

		$sixMinutesAgo = new \DateTimeImmutable("-6 minutes");

		\Tester\Assert::true($lockPush->checkAndLock($sixMinutesAgo));
		\Tester\Assert::true($lockPush->checkAndLock(new \DateTimeImmutable()));
	}


	protected function tearDown(): void
	{
		parent::tearDown();
		\Nette\Utils\FileSystem::delete(self::LOCK_FILE);
	}


}
(new PushLockTest())->run();
