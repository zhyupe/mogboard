<?php

namespace App\Command;

use App\Service\Alert\Alerts;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MogBoard_TriggerAlertsCommand extends Command
{
    use CommandConfigureTrait;

    const COMMAND = [
        'name' => 'MogBoard_TriggerAlertsCommand',
        'desc' => 'Trigger user alerts',
        'args' => [
            [ 'patrons', InputArgument::OPTIONAL, 'Filter for patron users?' ]
        ]
    ];

    /** @var Alerts */
    private $alerts;

    public function __construct(Alerts $alerts, $name = null)
    {
        $this->alerts = $alerts;
        parent::__construct($name);
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->alerts->trigger(
            !empty($input->getArgument('patrons'))
        );
    }
}
