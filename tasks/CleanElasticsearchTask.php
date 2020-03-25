<?php

require_once "phing/Task.php";

class CleanElasticsearchTask extends Task
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

        $host = $config['es-hosts'];

        $url = 'http://'.$host.'/magento2_*';

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_exec($curl);
        curl_close($curl);
    }
}