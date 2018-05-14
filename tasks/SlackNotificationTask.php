<?php

require_once 'AbstractRestApiCallTask.php';

class SlackNotificationTask extends AbstractRestApiCallTask
{
    /**
     * @var string
     */
    protected $webhookUrl;

    /**
     * @var string
     */
    protected $channel;

    /**
     * @var string
     */
    protected $message;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var string
     */
    protected $color = '#BCBCBE';

    /**
     * @param string $webhookUrl
     */
    public function setWebhookUrl(string $webhookUrl)
    {
        $this->webhookUrl = $webhookUrl;
    }

    /**
     * @param string $channel
     */
    public function setChannel(string $channel)
    {
        $this->channel = $channel;
    }

    /**
     * @param string $message
     */
    public function setMessage(string $message)
    {
        $this->message = $message;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title)
    {
        $this->title = $title;
    }

    /**
     * @param string $color
     */
    public function setColor(string $color)
    {
        $this->color = $color;
    }

    protected function prepareMessage(): array
    {
        $msg = [
            'channel' => $this->channel,
            'attachments' => [
                [
                    'link_names' => 1,
                    'color' => $this->color,
                    'text' => $this->message
                ]
            ]
        ];

        if ($this->title) {
            $msg['attachments']['title'] = $this->title;
        }

        return $msg;
    }

    protected function validateParams()
    {
        if (!$this->webhookUrl) {
            throw new BuildException('You must specify webhook url');
        }

        if (!$this->channel) {
            throw new BuildException('You must specify a channel');
        }

        if (!$this->message) {
            throw new BuildException('You must specify a message');
        }
    }

    public function main()
    {
        $this->validateParams();
        $this->request('POST', $this->webhookUrl, json_encode($this->prepareMessage()), false);
    }
}