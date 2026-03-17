<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\AddUserCommand;
use App\Entity\Receiver;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class AddUserCommandTest extends TestCase
{
    public function testExecuteFailsForDuplicateEmail(): void
    {
        $userRepository = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['findOneBy'])
            ->getMock();
        $userRepository->method('findOneBy')->willReturn(new User());

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturn($userRepository);

        $command = new AddUserCommand($entityManager);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'email' => 'user@example.com',
            'telegram' => '1234',
        ]);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('already exists', $tester->getDisplay());
    }

    public function testExecuteFailsWhenTelegramReceiverMissing(): void
    {
        $userRepository = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['findOneBy'])
            ->getMock();
        $receiverRepository = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['findOneBy'])
            ->getMock();

        $userRepository->method('findOneBy')->willReturn(null);
        $receiverRepository->method('findOneBy')->willReturn(null);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturnMap([
            [User::class, $userRepository],
            [Receiver::class, $receiverRepository],
        ]);

        $command = new AddUserCommand($entityManager);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'email' => 'user@example.com',
            'telegram' => '1234',
        ]);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Receiver `telegram` was not found', $tester->getDisplay());
    }
}
