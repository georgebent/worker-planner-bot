<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\AddRemindCommand;
use App\Entity\User;
use App\Entity\UserReceiver;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class AddRemindCommandTest extends TestCase
{
    public function testExecuteFailsForInvalidDateFormat(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $command = new AddRemindCommand($entityManager);

        $tester = new CommandTester($command);
        $exitCode = $tester->execute([
            'email' => 'user@example.com',
            'message' => 'hello',
            'send_at' => 'bad-date',
        ]);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Invalid `send_at` format', $tester->getDisplay());
    }

    public function testExecuteFailsWhenUserHasNoReceivers(): void
    {
        $user = new User();
        $user->setEmail('user@example.com');

        $userRepository = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['findOneBy'])
            ->getMock();
        $userRepository->method('findOneBy')->willReturn($user);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturn($userRepository);

        $command = new AddRemindCommand($entityManager);
        $tester = new CommandTester($command);
        $exitCode = $tester->execute([
            'email' => 'user@example.com',
            'message' => 'hello',
            'send_at' => '2026-01-01T10:00:00',
        ]);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('does not have any configured receivers', $tester->getDisplay());
    }

    public function testExecutePersistsJob(): void
    {
        $user = new User();
        $user->setEmail('user@example.com');
        $receiver = new UserReceiver();
        $receiver->setToken('t');
        $user->addUserReceiver($receiver);

        $userRepository = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['findOneBy'])
            ->getMock();
        $userRepository->method('findOneBy')->willReturn($user);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturn($userRepository);
        $entityManager->expects($this->once())->method('persist');
        $entityManager->expects($this->once())->method('flush');

        $command = new AddRemindCommand($entityManager);
        $tester = new CommandTester($command);
        $exitCode = $tester->execute([
            'email' => 'user@example.com',
            'message' => 'hello',
            'send_at' => '2026-01-01T10:00:00',
        ]);

        $this->assertSame(0, $exitCode);
    }
}
