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

use Cake\Database\Query;
use Cake\Database\QueryCompiler;
use Cake\Database\ValueBinder;

class OracleCompiler extends QueryCompiler
{
    /**
     * {@inheritDoc}
     */
    protected $_selectParts = [
        'select',
        'from',
        'join',
        'where',
        'group',
        'having',
        'order',
        'union',
        'epilog',
    ];

    /**
     * Builds the SQL fragment for INSERT INTO.
     *
     * @param array $parts The insert parts.
     * @param \Cake\Database\Query $query The query that is being compiled
     * @param \Cake\Database\ValueBinder $generator the placeholder generator to be used in expressions
     * @return string SQL fragment.
     */
    protected function _buildInsertPart(array $parts, Query $query, ValueBinder $generator): string
    {
        $driver = $query->getConnection()->getDriver();
        $table = $driver->quoteIfAutoQuote($parts[0]);
        $columns = $this->_stringifyExpressions($parts[1], $generator);
        $modifiers = $this->_buildModifierPart($query->clause('modifier'), $query, $generator);

        return sprintf('INSERT%s INTO %s (%s)', $modifiers, $table, implode(', ', $columns));
    }
}
