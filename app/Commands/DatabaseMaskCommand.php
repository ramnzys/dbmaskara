<?php

namespace App\Commands;

use Doctrine\DBAL\DriverManager;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Yaml\Yaml;
use Faker;

class DatabaseMaskCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'db:mask
                                {--url= : URL of the database to connect to.}
                                {--mask-config=example.yaml : Filename to output the result.}
    ';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->newLine();

        $inputFilename = $this->option('mask-config');

        $yamlConfig = Yaml::parseFile($inputFilename);

        $dbUrl =  $this->option('url') ?? $yamlConfig['default']['database']['url'] ?? '';

        if (!$dbUrl) {
            $this->error('No database connection specified. Use -h for help on command.');
            $this->newLine();
            return;
        }

        $conn = DriverManager::getConnection([
            'url' => $dbUrl,
        ]);

        $conn->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');

        $tables = $yamlConfig['tables'];

        foreach ($tables as $tableName => $tableData) {

            $this->info("\n\ndbMasking '{$tableName}' table ...");

            $primaryKeyValue = $yamlConfig['tables'][$tableName]['primary-key'];
            $primaryKeys = is_array($primaryKeyValue) ?  $primaryKeyValue :  explode(',', $primaryKeyValue);


            $dataResult = $conn->createQueryBuilder()->select('*')->from($tableName)->executeQuery();

            $progressBar = $this->output->createProgressBar($dataResult->rowCount());
            $progressBar->setBarWidth(40);
            $progressBar->setFormat('very_verbose');

            foreach ($dataResult->iterateAssociative() as $row) {
                $updateData = [];
                foreach ($yamlConfig['tables'][$tableName]['fields'] as $field => $fieldOptions) {
                    $valueGenerator = Faker\Factory::create('es_es');
                    $params = $fieldOptions['params'] ?? [];
                    $updateValue = $valueGenerator->__call($fieldOptions['formatter'], $params);
                    $updateData += array($field => $updateValue);
                }

                $keyData = array_flip($primaryKeys);
                foreach ($keyData as $key => $value) {
                    $keyData[$key] = $row[$key];
                }

                $conn->update($tableName, $updateData, $keyData);
                $progressBar->advance();
            }
            $progressBar->finish();
        }

        $this->newLine();
        $this->info('Done!');
        $this->newLine();
    }

    /**
     * Define the command's schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
