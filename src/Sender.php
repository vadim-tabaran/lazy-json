<?php
namespace LazyJson;

class Sender
{
    const APPEND_METHOD_NAME = 'append';

    private $destination;
    private $errors = array();

    /**
     * Receiver constructor.
     *
     * @param null $destination object|resource|null
     */
    public function __construct($destination = null)
    {
        $this->destination = $destination;
    }

    /**
     * Append data to destination
     *
     * @param string $key Parameter key
     * @param !resource $value Parameter value
     *
     * @return bool|mixed
     */
    public function append($key, $value)
    {
        $preparedData = $this->prepareData($key, $value);

        return $this->sendToDestination($preparedData);
    }

    /**
     * Prepare data, serialising it into a JSON string
     *
     * @param string $key Parameter key
     * @param !resource $value Parameter value
     *
     * @return string
     */
    private function prepareData($key, $value)
    {
        $json = json_encode(array('key' => $key, 'value' => $value));

        return strlen($json) . ':' . $json;
    }

    /**
     * Sends data to defined destination
     *
     * @param string $data Prepared string
     *
     * @return bool|mixed
     */
    private function sendToDestination($data)
    {
        $destinationType = strtolower(gettype($this->destination));

        switch ($destinationType) {
            case 'null':
                return $data;
                break;
            case 'resource':
                return true;
                break;
            case 'object':
                return $this->appendCustom($data);
                break;
            default:
                return false;
        }
    }

    /**
     * Call custom method for sending
     *
     * @param string $data Prepared string
     *
     * @return bool|mixed
     */
    private function appendCustom($data)
    {
        if (!method_exists($this->destination, self::APPEND_METHOD_NAME)) {
            $this->appendError("Object has no method " . self::APPEND_METHOD_NAME);
            return false;
        }

        return call_user_func_array(
            array($this->destination, self::APPEND_METHOD_NAME),
            array($data)
        );
    }

    /**
     * Append error
     *
     * @param mixed $error Error entity
     */
    private function appendError($error)
    {
        $this->errors[] = $error;
    }

    /**
     * Get list of errors
     *
     * @return array List of errors
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
