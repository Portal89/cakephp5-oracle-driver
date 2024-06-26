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
namespace Portal89\OracleDriver\Database\Schema;

use Cake\Database\Exception;
use Cake\Datasource\ConnectionInterface;
use PDOException;

/**
 * Represents a database methods collection
 *
 * Used to access information about the methods,
 * and other data in a database.
 */
class MethodsCollection
{
    /**
     * Connection object
     *
     * @var \Cake\Datasource\ConnectionInterface
     */
    protected $_connection;

    /**
     * Schema dialect instance.
     *
     * @var \Cake\Database\Schema\BaseSchema
     */
    protected $_dialect;

    /**
     * Constructor.
     *
     * @param \Cake\Datasource\ConnectionInterface $connection The connection instance.
     */
    public function __construct(ConnectionInterface $connection)
    {
        $this->_connection = $connection;
        $this->_dialect = $connection->getDriver()->schemaDialect();
    }

    /**
     * Get the list of methods available in the current connection.
     *
     * @return array The list of methods in the connected database/schema.
     */
    public function listMethods()
    {
        // @todo fix this method to return only high level data
        [$sql, $params] = $this->_dialect->listMethodsSql($this->_connection->config());
        $result = [];
        $statement = $this->_connection->execute($sql, $params);
        while ($row = $statement->fetch()) {
            $result[] = $row[0];
        }
        $statement->closeCursor();

        return $result;
    }

    /**
     * Get the list of methods available in the current connection.
     *
     * @param string $name Method name.
     * @return array The list of methods in the connected database/schema.
     */
    public function getMethod($name)
    {
        $config = $this->_connection->config();
        $config['objectName'] = $name;
        [$sql, $params] = $this->_dialect->listMethodsSql($config);
        $result = [];
        $statement = $this->_connection->execute($sql, $params);
        while ($row = $statement->fetch()) {
            $result[] = $row[0];
        }
        $statement->closeCursor();

        return $result;
    }

    /**
     * Get the metadata for a method.
     *
     * Caching will be applied if `cacheMetadata` key is present in the Connection
     * configuration options. Defaults to _cake_method_ when true.
     *
     * ### Options
     *
     * - `forceRefresh` - Set to true to force rebuilding the cached metadata.
     *   Defaults to false.
     *
     * @param string $name The name of the method to describe.
     * @param array $options The options to use, see above.
     * @return \Portal89\OracleDriver\Database\Schema\MethodSchema Object with method metadata.
     * @throws \Cake\Database\Exception when method cannot be described.
     */
    public function describe($name, array $options = [])
    {
        $config = $this->_connection->config();
        $methods = $this->getMethod($name);
        if (empty($methods)) {
            throw new Exception(sprintf('Cannot describe %s. Method not found.', $name));
        }
        $method = new MethodSchema($name);

        $this->_reflect($method, $name, $config);

        return $method;
    }

    /**
     * Helper method for running each step of the reflection process.
     *
     * @param \Portal89\OracleDriver\Database\Schema\MethodSchema $method Object with method metadata.
     * @param string $name The method name.
     * @param array $config The config data.
     * @return void
     * @throws \Cake\Database\Exception on query failure.
     */
    protected function _reflect($method, $name, $config)
    {
        [$sql, $params] = $this->_dialect->describeParametersSql($name, $config);
        if (empty($sql)) {
            return;
        }
        try {
            $statement = $this->_connection->execute($sql, $params);
        } catch (PDOException $e) {
            throw new Exception($e->getMessage(), 500, $e);
        }
        foreach ($statement->fetchAll('assoc') as $row) {
            $this->_dialect->convertParametersDescription($method, $row);
        }
        $statement->closeCursor();
    }
}
