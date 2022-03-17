<?php

namespace App\Commands;

use Doctrine\DBAL\DriverManager;
use Exception;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class DatabaseScanCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'db:scan
                                {--url= : URL of the database to connect to.}
                                {--o|output-file=example.yaml : Filename to output the result.}
    ';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Scan database structure to generate sample configuration file.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->newLine();

        $url = $this->option('url');
        $outputFilename = $this->option('output-file');

        if (is_null($url) || is_null($outputFilename)) {
            $this->error('Bad parameters. Use -h for help on command.');
            $this->newLine();
            return 1;
        }

        $conn = DriverManager::getConnection([
            'url' => $this->option('url')
        ]);

        try {
            $conn->connect();
        } catch (Exception $e) {
            $this->error('Unable to connect to database.');
            $this->newLine();
            $this->line($e->getMessage());
            $this->newLine();
            return 1;
        }

        $conn->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');

        $sm = $conn->createSchemaManager();
        $qb = $conn->createQueryBuilder();

        // Create and write header file
        $output = fopen($outputFilename, 'w');
        fwrite($output, "default:\n  database:\n    url: {$this->option('url')}\n  mask:\n");
        fwrite($output, "    generator: faker\n");
        fwrite($output, "    locale: es_es\n");
        fwrite($output, "    formatter: word\n");
        fwrite($output, "    arguments: [5]\n");
        fwrite($output, "tables:\n");

        $tables = $sm->listTables();

        foreach ($tables as $table) {

            $tableName = $table->getName();

            $primaryKeyColumnNames = $this->getPrimaryKeyColumnNames($table);
            $foreignKeyColumnNames = $this->getForeignKeyColumnNames($table);

            fwrite($output, "  {$tableName}:\n");
            fwrite($output, "    primary-key: " . implode(",", $primaryKeyColumnNames) . "\n");


            fwrite($output, "    fields:\n");

            $columnNames = array_keys($sm->listTableColumns($tableName));

            foreach ($columnNames as $columnName) {
                $isPrimary = in_array($columnName, $primaryKeyColumnNames);
                $isForeign = in_array($columnName, $foreignKeyColumnNames);
                if (!($isPrimary || $isForeign)) {
                    fwrite($output, "      {$columnName}:\n");
                }
            }
        }

        fclose($output);

        $this->info("YAML file generated: {$outputFilename}");

        $this->newLine();
    }

    /**
     * Get primary key table names
     * @param \Doctrine\DBAL\Schema\Table $table $table
     * @return array
     */
    private function getPrimaryKeyColumnNames($table)
    {
        $columns = $table->hasPrimaryKey() ? $table->getPrimaryKeyColumns() : [];
        return array_keys($columns);
    }

    /**
     *
     * @param \Doctrine\DBAL\Schema\Table $table
     * @return array
     */
    private function getForeignKeyColumnNames($table)
    {
        $columns = $table->getForeignKeyColumns();
        return array_keys($columns);
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
