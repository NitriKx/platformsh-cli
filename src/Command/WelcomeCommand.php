<?php

namespace Platformsh\Cli\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class WelcomeCommand extends CommandBase
{
    protected static $defaultName = 'welcome';

    protected function configure()
    {
        $this->setDescription('Welcome to ' . $this->config()->get('service.name'));
        $this->setHidden(true);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->stdErr->writeln("Welcome to " . $this->config()->get('service.name') . "!\n");

        // Ensure the user is logged in in this parent command, because the
        // delegated commands below will not have interactive input.
        $this->api()->getClient();

        $executable = $this->config()->get('application.executable');

        if ($project = $this->getCurrentProject()) {
            $projectUri = $project->getLink('#ui');
            $this->stdErr->writeln("Project title: <info>{$project->title}</info>");
            $this->stdErr->writeln("Project ID: <info>{$project->id}</info>");
            $this->stdErr->writeln("Project dashboard: <info>$projectUri</info>\n");

            // Warn if the project is suspended.
            if ($project->isSuspended()) {
                $messages = [];
                $messages[] = '<comment>This project is suspended.</comment>';
                if ($project->owner === $this->api()->getMyAccount()['uuid']) {
                    $messages[] = '<comment>Update your payment details to re-activate it: '
                        . $this->config()->get('service.accounts_url')
                        . '</comment>';
                }
                $messages[] = '';
                $this->stdErr->writeln($messages);
            }

            // Show the environments.
            $this->runOtherCommand('environment:list', ['--refresh' => 0]);
            $this->stdErr->writeln("\nYou can list other projects by running <info>$executable projects</info>\n");
        } else {
            // The project is not known. Show all projects.
            $this->runOtherCommand('project:list', ['--refresh' => 0]);
            $this->stdErr->writeln('');
        }

        $this->stdErr->writeln("Manage your SSH keys by running <info>$executable ssh-keys</info>\n");

        $this->stdErr->writeln("Type <info>$executable list</info> to see all available commands.");
    }
}
