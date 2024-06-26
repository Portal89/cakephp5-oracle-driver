<?php
declare(strict_types=1);

/**
 * Copyright 2024, Portal89 (https://portal89.com.br)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2024, Portal89 (https://portal89.com.br)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Portal89\OracleDriver\Database;

use Cake\Core\Exception\Exception;
use Cake\Database\Connection;
use Cake\Database\StatementInterface;
use Portal89\OracleDriver\Database\Log\MethodLogger;
use Portal89\OracleDriver\Database\Log\MethodLoggingStatement;
use Portal89\OracleDriver\Database\Schema\CachedMethodsCollection;
use Portal89\OracleDriver\Database\Schema\MethodsCollection;

class OracleConnection extends Connection
{
    /**
     * Driver object, responsible for creating the real connection
     * and provide specific SQL dialect.
     *
     * @var \Portal89\OracleDriver\Database\Driver\OracleBase
     */
    protected $_driver;

    /**
     * Logger object instance.
     *
     * @var \Portal89\OracleDriver\Database\Log\MethodLogger
     */
    protected $_methodLogger = null;

    /**
     * The methods collection object
     *
     * @var \Portal89\OracleDriver\Database\Schema\MethodsCollection
     */
    protected $_schemaMethodsCollection;

    /**
     * Builds oracle connection based on generic cakephp connection class.
     *
     * @param \Cake\Database\Connection $connection Connection object.
     * @return \Portal89\OracleDriver\Database\OracleConnection
     */
    public static function build(Connection $connection)
    {
        $config = $connection->config();
        $config['driver'] = $connection->getDriver();

        return new OracleConnection($config);
    }

    /**
     * Gets or sets a Schema\Collection object for this connection.
     *
     * @param \Portal89\OracleDriver\Database\Schema\MethodsCollection|null $collection The schema collection object
     * @return \Portal89\OracleDriver\Database\Schema\MethodsCollection
     */
    public function methodSchemaCollection(?MethodsCollection $collection = null)
    {
        if ($collection !== null) {
            return $this->_schemaMethodsCollection = $collection;
        }

        if ($this->_schemaMethodsCollection !== null) {
            return $this->_schemaMethodsCollection;
        }

        if (!empty($this->_config['cacheMetadata'])) {
            return $this->_schemaMethodsCollection = new CachedMethodsCollection($this, $this->_config['cacheMetadata']);
        }

        return $this->_schemaMethodsCollection = new MethodsCollection($this);
    }

    /**
     * Prepares a PL/SQL statement to be executed.
     *
     * @param string $sql The PL/SQL to convert into a prepared statement.
     * @param array $options Method options used on method constructing.
     * @return \Cake\Database\StatementInterface
     */
    public function prepareMethod($sql, $options = [])
    {
        if (!method_exists($this->_driver, 'isOci') || !$this->_driver->isOci()) {
            throw new Exception('Method calls using PDO layer not supported');
        }
        $options += ['bufferResult' => false];
        $statement = $this->_driver->prepareMethod($sql, $options);
        $this->_logQueries = true;
        if ($this->_logQueries) {
            $statement = $this->_getMethodLogger($statement);
        }

        return $statement;
    }

    /**
     * Returns a new statement object that will log the activity
     * for the passed original statement instance.
     *
     * @param \Cake\Database\StatementInterface $statement the instance to be decorated
     * @return \Cake\Database\StatementInterface
     */
    protected function _getMethodLogger(StatementInterface $statement)
    {
        $log = new MethodLoggingStatement($statement, $this->getDriver());
        $log->logger($this->methodLogger());

        return $log;
    }

    /**
     * Sets the method logger object instance. When called with
     * no arguments it returns the currently setup logger instance.
     *
     * @param \Portal89\OracleDriver\Database\Log\MethodLogger $instance logger object instance
     * @return object logger instance
     */
    public function methodLogger(?MethodLogger $instance = null)
    {
        if ($instance === null) {
            if ($this->_methodLogger === null) {
                $this->_methodLogger = new MethodLogger();
            }

            return $this->_methodLogger;
        }
        $this->_methodLogger = $instance;
    }

    /**
     * @inheritDoc
     */
    public function cacheMetadata($cache): void
    {
        $this->_schemaMethodsCollection = null;
        parent::cacheMetadata($cache);
    }
}
