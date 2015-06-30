<?php

namespace Utils;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Mvc\MvcEvent;

class MessageCommunicator extends AbstractActionController
{

    public $allowedTypes = ['error', 'success', 'info', 'warning'];

    public function addMessage($message, $type)
    {
        if (is_array($message) || trim($message) <> '' && trim($type) <> '' && in_array($type, $this->allowedTypes)) {
            $this->flashMessenger()->setNamespace($type)->addMessage($message);
        } else {
            throw new \Exception('Could not get message to add ');
        }
        return true;
    }

    public function getMessages()
    {
        $messages = [];
        foreach ($this->allowedTypes as $val) {
            if ($this->flashMessenger()->setNamespace($val)->hasMessages()) {
                $messages[$val] = $this->flashMessenger()->setNamespace($val)->getMessages();
                $this->flashMessenger()->clearMessages();
            }
        }
        return $messages;
    }

    public function clearMessages() {
        $this->flashMessenger()->clearMessages();
        return true;
    }

    /**
     * @function        = formErrorHandler()
     * @param $array
     * @param string $class
     * @throws \Exception
     * @internal param $ = accept the array parameter variable
     * @description     = used to handle the form errors
     */
    public function formErrorHandler($array, $class = 'error')
    {

        if (is_array($array)) {

            // flashMessenger is used to populate any kind of message here is create a message
            // we can use the message in the view renderer to populate and error.

            if ( trim($class) == 'Please fix file/folder permissions.' ) {
                $this->flashMessenger()->setNamespace('error')->addMessage($class);
                $class = 'error';
            } else {
                $this->flashMessenger()->setNamespace($class)->addMessage('Please fix the following input errors:');
            }
            
            foreach ($array as $key => $value) {

                if (is_array($value)) {

                    foreach ($value as $keyval => $innerval) {
                        /**
                         * @function       = converToLable()
                         * @param     	   = accept error message lable for
                         * @description    = convert to uppercase words
                         */

                        $this->flashMessenger()->setNamespace($class)->addMessage( $this->converToLable($key) . $innerval );
                    }
                }
                else{
                    $this->flashMessenger()->setNamespace($class)->addMessage( $this->converToLable('Uploaded File') . $value );
                }
            }

        } else {
            throw new \Exception('Invalid parameter array is required.');
        }

    }

    /**
     * @function       = converToLable()
     * @param string $value
     * @return string
     * @internal param $ = accept error message lable like zone_code
     * @description    = convert to uppercase words like Zone Code
     */

    public function converToLable($value = '')
    {
        return ucwords( str_replace('_', ' ', $value) ) . ': ';
    }
}