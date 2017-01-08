<?php
namespace LazyJson;

class Receiver
{
    private $resource = null;
    private $oneStepLength = 8000;
    private $errors = array();

    /**
     * Receiver constructor.
     *
     * @param string|resource $resource Resource list
     *      ( link on resource or path to file )
     */
    public function __construct($resource = null)
    {
        if (!is_null($resource)) {
            if (is_resource($resource)) {
                $this->setResource($resource);
            } elseif (is_string($resource)) {
                $this->setResource(fopen($resource, 'rb'));
            }
        }
    }

    /**
     * Set resource
     *
     * @param resource|string $resource Resource
     * @param bool $fromString Create resource from string
     *
     * @return bool
     */
    private function setResource($resource, $fromString = false)
    {
        if (!$resource) {
            $this->appendError('Invalid resource');
            return false;
        }

        if (is_resource($resource)) {
            $this->resource = $resource;
            return true;
        }

        if (is_string($resource)) {
            if ($fromString === true) {
                $source = 'data://text/plain,' . $resource;
            } else {
                $source = $resource;
            }

            return $this->setResource(fopen($source, 'rb'));
        }

        return false;
    }

    /**
     * Append error
     *
     * @param mixed $error Error details
     */
    private function appendError($error)
    {
        $this->errors[] = $error;
    }

    /**
     * Get next part of data
     *
     * @return mixed
     */
    public function getPart()
    {
        $currentPartLength = '';
        while (($character = fgetc($this->resource)) !== ':') {
            if (!is_numeric($character)) {
                return false;
            }
            $currentPartLength .= $character;
        }

        $totalLength = intval($currentPartLength);

        $jsonBuffer = '';

        while (($curLength = strlen($jsonBuffer)) != $totalLength) {
            // PHP stream buffering limitations for non file source
            // http://php.net/manual/en/function.fread.php#refsect1-function.fread-description
            if ($curLength + $this->oneStepLength > $totalLength) {
                $curStep = $totalLength - $curLength;
            } else {
                $curStep = $this->oneStepLength;
            }

            $jsonBuffer .= fread($this->resource, $curStep);
        }

        return json_decode($jsonBuffer);
    }

    /**
     * Is the end of data stream ?
     *
     * @return bool
     */
    public function isEnd()
    {
        return feof($this->resource);
    }
}
