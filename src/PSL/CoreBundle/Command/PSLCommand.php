<?php

namespace PSL\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

use Psr\Log\LoggerInterface;

/**
 * Description of CoreCommand
 *
 * @author David Tremblay-Fafard <david.tremblay@firstwordgroup.com>
 */
abstract class PSLCommand extends ContainerAwareCommand
{
    use LockableTrait;

    /**
     *
     * @var OutputInterface 
     */
    protected $output;

    /**
     *
     * @var InputInterface 
     */
    protected $input;
    
    /**
     *
     * @var LoggerInterface  
     */
    protected $logger;

    /**
     * 
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        
        parent::__construct();
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * PSL Commands are required to return debug information about :
     *              - When they started and ended
     *              - How long they took
     *              - The memory usage
     *
     * It is required to manage exceptions via logging systems.
     *
     * It is required to allow locking mechanism in order to avoid multiple executions at the same time.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws RuntimeException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $start = time();

        try {
            $this->logger->info('Job Started : ' . date('m-d-Y H:i:s'));
            $this->logger->info('Memory at the beginning : ' . memory_get_usage());

            $this->output = $output;
            $this->input = $input;

            if (
                !$this->input->getOption('skip-lock')
                && !$this->lock()
            ) {
                throw new \Exception('The script is locked. Use the option --skip-lock if you wish to force the script to run.');
            }

            $this->do();
            $this->logger->info('You have executed this command successfully! You gained 3864 exp point. You may proceed to the next level.');
        } catch (\Exception $e) {
            $this->logger->critical(sprintf($this->getName() . ' : System Crash. Error : %s', $e->getMessage()));
        } finally {
            $this->logger->info('Memory at the end : ' . memory_get_usage());
            $this->logger->info('Peak memory : ' . memory_get_peak_usage());
            $this->logger->info('Job Ended : ' . date('m-d-Y H:i:s'));

            $end = time();
            $duration = $end - $start;
            $durationFormatted = gmdate("H:i:s", $duration);
            $this->logger->info(sprintf('Job took %s to execute', $durationFormatted));

            $this->release();
        }
    }

    /**
     * Configuration required for PSL Commands
     */
    protected function configure()
    {
        $this->addOption('skip-lock', 'sl', InputOption::VALUE_NONE, 'Option is to skip the lock if specifically requested.');
    }

    /**
     * The function containing
     */
    abstract protected function do() : void;
}
