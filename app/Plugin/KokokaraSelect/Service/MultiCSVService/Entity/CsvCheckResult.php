<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 *
 */

namespace Plugin\KokokaraSelect\Service\MultiCSVService\Entity;


class CsvCheckResult
{

    private $success;

    private $message;

    public function __construct()
    {
        $this->success = false;
        $this->message = "";
    }

    /**
     * @return bool
     */
    public function isSuccess()
    {
        return $this->success;
    }

    /**
     * @param bool $success
     * @return $this
     */
    public function setSuccess(bool $success)
    {
        $this->success = $success;
        return $this;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param string $message
     * @return $this
     */
    public function setMessage(string $message)
    {
        $this->message = $message;
        return $this;
    }
}
