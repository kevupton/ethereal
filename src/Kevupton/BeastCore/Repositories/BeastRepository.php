<?php namespace Kevupton\BeastCore\Repository;

abstract class BeastRepository {

    protected $exceptions = [];

    /**
     * Retrieves the class instance of the specified repository.
     *
     * @return string the string instance of the defining class
     */
    abstract function getClass();

    /**
     * Retrieves the which exception class to use.
     *
     * @param string $exception the exception to throw.
     * @return string the string instance of the exception class
     */
    public function getException($exception = 'main') {
        if (isset($this->exceptions[$exception]) && !empty($this->exceptions[$exception])) {
            return $this->exceptions[$exception];
        } else {
            if ($exception == 'main') {
                return \Exception::class;
            } else {
                return $this->getException();
            }
        }
    }

    /**
     * Attempts to retrieve the Ticket by the given ticket ID.
     *
     * @param int $id the id of the ticket
     * @return \stdClass an instance of the Repository class.
     * @throws \Exception of specified type if it is not found.
     */
    public final function retrieveByID($id) {
        $class = $this->getClass();
        try {
            return $class::findOrFail($id);
        } catch(\Exception $e) {
            $this->throwException("$class id: $id not found");
        }
    }

    /**
     * Throws a specified exception under a specific category with a specified message
     *
     * @param string $message the message of the exception
     * @param string $exception the exception category to throw ex. main
     */
    public function throwException($message = '', $exception = 'main') {
        $class = $this->getException($exception);
        throw new $class($message);
    }

}