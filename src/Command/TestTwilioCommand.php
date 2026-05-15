<?php

namespace App\Command;

use App\Service\TwilioService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:test-twilio', description: 'Send a test SMS via Twilio')]
class TestTwilioCommand extends Command
{
    public function __construct(private readonly TwilioService $twilioService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('phone', InputArgument::REQUIRED, 'Phone number in E.164 format (e.g. +21612345678)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $phone = $input->getArgument('phone');
        $output->writeln("Sending test SMS to $phone ...");

        $ok = $this->twilioService->sendSms($phone, 'Travigir: Twilio SMS test successful!');

        if ($ok) {
            $output->writeln('<info>SMS sent successfully!</info>');
            return Command::SUCCESS;
        }

        $output->writeln('<error>SMS failed — check logs for details.</error>');
        return Command::FAILURE;
    }
}
