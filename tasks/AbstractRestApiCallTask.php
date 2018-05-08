<?php

require_once "phing/Task.php";

abstract class AbstractRestApiCallTask extends Task
{
    protected function request(string $method, string $url, string $payload, bool $failOnErrors = true, string $contentType = 'application/json', callable $handleResponse = null)
    {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Content-type: ' . $contentType,
            'Content-length: ' . strlen($payload),
        ]);

        $responsePayload = curl_exec($curl);
        $responseCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        $errorCode = curl_errno($curl);
        $errorMessage = curl_error($curl);

        curl_close($curl);

        if (0 !== $errorCode) {
            $message = "cURL error ($errorCode): $errorMessage";

            if ($failOnErrors) {
                throw new BuildException($message);
            } else {
                $this->log($message, Project::MSG_ERR);
            }
        }

        if ($responseCode >= 400) {
            $message = "Error calling ${method} ${url}, got ${responseCode} response with body: ${responsePayload}";

            if ($failOnErrors) {
                throw new BuildException($message);
            } else {
                $this->log($message, Project::MSG_ERR);
            }
        }

        if (null !== $handleResponse) {
            $handleResponse($responseCode, $responsePayload);
        }
    }
}