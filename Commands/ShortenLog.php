<?php
/**
 * Piwik - Open source web analytics
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\VisitorGenerator\Commands;

use Piwik\Common;
use Piwik\Date;
use Piwik\Filesystem;
use Piwik\Plugin\ConsoleCommand;
use Piwik\Plugins\VisitorGenerator\LogParser;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ShortenLog extends ConsoleCommand
{
    protected function configure()
    {
        $this->setName('visitorgenerator:shorten-log');
        $this->setHelp('Example usage:
<comment>./console visitorgenerator:shorten-log /path/to/file.log > file.short.log</comment>');
        $this->setDescription('Shortens an Apache log file by keeping only a small number of logs per day.');
        $this->addArgument('file', InputArgument::REQUIRED, 'Path to the log file. Either an absolute path or a path relative to the Piwik directory');
        $this->addOption('num-lines', null, InputOption::VALUE_REQUIRED, 'Max number of log lines to keep per day', 250);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $file     = $this->getPathToFile($input);
        $numLines = $input->getOption('num-lines');

        $logParser = new LogParser(array($file));
        $lines     = $logParser->getLogLines();

        $shortened = '';
        $lastDate  = null;
        $counter   = 1;

        foreach ($lines as $line) {
            $parsed = LogParser::parseLogLine($line);

            if ($lastDate != $this->getDateFromTime($parsed)) {
                $counter  = 1;
                $lastDate = $this->getDateFromTime($parsed);
            }

            if ($counter > $numLines) {
                continue;
            }

            $shortened .= $line;
            $counter++;
        }

        echo $shortened;
    }

    private function getPathToFile(InputInterface $input)
    {
        $file = $input->getArgument('file');

        if (file_exists($file)) {
            return $file;
        }

        if (file_exists(PIWIK_INCLUDE_PATH . '/' . $file)) {
            return PIWIK_INCLUDE_PATH . '/' . $file;
        }

        throw new \InvalidArgumentException('Cannot find file, please specify an absoulute path and make sure the file is readable.');
    }

    private function getDateFromTime($parsed)
    {
        return Date::factory($parsed['time'])->toString('Y-m-d');
    }

}
