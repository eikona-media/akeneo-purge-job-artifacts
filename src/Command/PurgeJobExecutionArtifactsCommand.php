<?php
/**
 * PurgeJobExecutionArtifactsCommand.php
 *
 * @author      Timo Müller <t.mueller@eikona-media.de>
 * @copyright   2019 EIKONA Media (https://eikona-media.de)
 */

namespace EikonaMedia\Akeneo\PurgeJobArtifactsBundle\Command;

use Akeneo\Platform\Bundle\ImportExportBundle\Repository\InternalApi\JobExecutionRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

class PurgeJobExecutionArtifactsCommand extends Command
{
    /** @var JobExecutionRepository */
    protected $jobExecutionRepository;

    /** @var string */
    protected $archiveDir;

    /**
     * @param JobExecutionRepository $jobExecutionRepository
     * @param string $archiveDir
     */
    public function __construct(
        JobExecutionRepository $jobExecutionRepository,
        string $archiveDir
    )
    {
        parent::__construct();
        $this->jobExecutionRepository = $jobExecutionRepository;
        $this->archiveDir = $archiveDir;
    }

    protected function configure()
    {
        $this
            ->setName('eikona-media:batch:purge-job-execution-artifacts')
            ->setDescription('Remove files from removed job executions')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force deletion');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $force = $input->getOption('force');
        $fs = new Filesystem();

        if (!$force) {
            $io->warning('Command running in safe mode. Use --force to delete files.');
        }

        $purgableJobExections = $this->getPurgableJobExecutionsFromFileSystem();
        foreach($purgableJobExections as $purgableJobExection) {
            if ($force) {
                $fs->remove($purgableJobExection['path']);
            }
            $io->writeln(sprintf(
                'Removed archive directory "%s"',
                $purgableJobExection['path']
            ));
        }

        $io->success(sprintf('Removed %d job execution archive directories', count($purgableJobExections)));
    }

    /**
     * @return array
     */
    protected function getPurgableJobExecutionsFromFileSystem()
    {
        $jobExecutions = [];

        $directories = $this->getDirectories($this->archiveDir);
        foreach ($directories as $directoryPath) {
            $directoryName = basename($directoryPath);
            @array_push($jobExecutions, ...$this->getJobExectionsFromFileSystem($directoryPath, $directoryName));
        }

        $jobExecutionsDeleted = array_filter($jobExecutions, function ($jobExecution) {
            return $this->jobExecutionRepository->find($jobExecution['id']) === null;
        });

        return $jobExecutionsDeleted;
    }

    /**
     * @param $dir
     * @param $type
     * @return array
     */
    protected function getJobExectionsFromFileSystem($dir, $type)
    {
        $jobExecutions = [];
        foreach ($this->getDirectories($dir) as $jobTypeDir) {
            $jobType = basename($jobTypeDir);

            foreach ($this->getDirectories($jobTypeDir) as $jobExecutionDir) {
                $jobId = basename($jobExecutionDir);
                if (!preg_match('/\d+/', $jobId)) {
                    continue;
                }

                $jobExecutions[] = [
                    'id' => $jobId,
                    'type' => $type,
                    'job' => $jobType,
                    'path' => $jobExecutionDir
                ];
            };
        }
        return $jobExecutions;
    }

    /**
     * @param $path
     * @return array
     */
    protected function getDirectories($path)
    {
        $filesAndDirs = scandir($path);
        $dirs = array_filter($filesAndDirs, function ($fileOrDir) use($path) {
            return $fileOrDir !== '.' && $fileOrDir !== '..' && is_dir($path . DIRECTORY_SEPARATOR . $fileOrDir);
        });
        return array_map(function($dir) use ($path) {
            return $path . DIRECTORY_SEPARATOR . $dir;
        }, $dirs);
    }
}
