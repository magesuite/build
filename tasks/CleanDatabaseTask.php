<?php

require_once "phing/Task.php";

class CleanDatabaseTask extends Task
{
    /**
     * @var string
     */
    protected $configPath;

    /**
     * @var string
     */
    protected $bootstrapPath;

    public function setConfigPath(string $configPath) {
        $this->configPath = $configPath;
    }

    public function setBootstrapPath(string $bootstrapPath) {
        $this->bootstrapPath = $bootstrapPath;
    }
    /**
     * @inheritDoc
     */
    public function main()
    {
        include_once $this->bootstrapPath;

        $configPath = $this->configPath;

        $config = include $configPath;

        $host = $config['db-host'];
        $user = $config['db-user'];
        $password = $config['db-password'];
        $name = $config['db-name'];

        $connection = new \PDO('mysql:host='.$host.';dbname='.$name, $user, $password);

        $connection->prepare('SET foreign_key_checks = 0')->execute();

        $statement = $connection->prepare('SHOW FULL TABLES');
        $statement->execute();

        foreach($statement->fetchAll() as $table) {
            $tableName = $table[0];
            $tableType = $table[1];

            if($tableType === 'VIEW') {
                continue;
            }

            $connection->prepare('DROP TABLE ' . $tableName)->execute();
        }

        $statement = $connection->prepare('SELECT * FROM information_schema.views WHERE table_schema = :db_name');
        $statement->execute(['db_name' => $name]);

        foreach($statement->fetchAll() as $view) {
            $viewName = $view['TABLE_NAME'];

            $connection->prepare('DROP VIEW ' . $viewName)->execute();
        }

        $connection->prepare('SET foreign_key_checks = 1')->execute();
    }
}
