<?php

namespace App\Command;

use App\Entity\Job;
use App\Entity\User;
use App\Entity\UserReceiver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:remind:create',
    description: 'Add a remind',
)]
class AddRemindCommand extends Command
{
    public function __construct(private EntityManagerInterface $entityManager, string $name = null)
    {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'email')
            ->addArgument('message', InputArgument::REQUIRED, 'message')
            ->addArgument('send_at', InputArgument::REQUIRED, 'send_at')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = (string) $input->getArgument('email');
        $message = (string) $input->getArgument('message');
        $sendAtInput = (string) $input->getArgument('send_at');
        $sendAt = \DateTimeImmutable::createFromFormat('Y-m-d\\TH:i:s', $sendAtInput);

        if (!$sendAt) {
            $output->writeln('Invalid `send_at` format. Expected: Y-m-d\\TH:i:s');

            return Command::FAILURE;
        }

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if (!$user) {
            $output->writeln(sprintf('User with email `%s` was not found.', $email));

            return Command::FAILURE;
        }

        $userReceiver = $user->getUserReceivers()->first();
        if (!$userReceiver instanceof UserReceiver) {
            $output->writeln(sprintf('User `%s` does not have any configured receivers.', $email));

            return Command::FAILURE;
        }

        $job = new Job();
        $job->setMessage($message);
        $job->setSendAt($sendAt);
        $job->setSentTimes(0);
        $job->setMaxTimes(1);
        $job->setUserReceiver($userReceiver);

        $this->entityManager->persist($job);
        $this->entityManager->flush();

        return Command::SUCCESS;
    }
}
