<?php

require_once 'AbstractRestApiCallTask.php';

class TvNotificationTask extends AbstractRestApiCallTask
{
    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $message;

    /**
     * @var string
     */
    protected $say;

    /**
     * @param string $type
     */
    public function setType(string $type)
    {
        $this->type = $type;
    }

    /**
     * @param string $message
     */
    public function setMessage(string $message)
    {
        $this->message = $message;
    }

    /**
     * @param string $say
     */
    public function setSay(string $say)
    {
        $this->say = $say;
    }

    protected function prepareMessage(): array
    {
        $msg = [
            'body' => $this->message,
            'cover' => 'https://wiki.jenkins.io/download/attachments/2916393/logo.png?version=1&modificationDate=1302753947000&api=v2',
        ];

        if ($this->type) {
            $msg['type'] = $this->type;
        }

        if ($this->say) {
            $msg['say'] = $this->say;
        }

        return $msg;
    }

    protected function validateParams()
    {
        if (!in_array($this->type, ['warning', 'success', 'alert', 'normal'])) {
            throw new BuildException('You must specify webhook url');
        }

        if (!$this->message) {
            throw new BuildException('You must specify a message');
        }
    }

    public function main()
    {
        $this->validateParams();
        $this->request('POST', 'http://tv.creativestyle.pl/fact', json_encode($this->prepareMessage()), false);
    }
}