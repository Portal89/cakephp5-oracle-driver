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

/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase;

use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestSuite;
use PHPUnit\Framework\TestResult;

/**
 * All tests related to database
 *
 */
class DatabaseSuite extends TestSuite
{
    /**
     * Returns a suite containing all tests requiring a database connection,
     * tests are decorated so that they are run once with automatic
     *
     * @return void
     */
    public static function suite()
    {
        $suite = new self('Database related tests');
        // $suite->addTestFile(__DIR__ . DS . 'Database' . DS . 'ConnectionTest.php');
        $suite->addTestDirectoryRecursive(__DIR__ . DS . 'Database');
        $suite->addTestDirectoryRecursive(__DIR__ . DS . 'ORM');

        return $suite;
    }

//     public function count(bool $preferCache = false): int
//     {
//        return parent::count() * 2;
//     }

    /**
     * Runs the tests and collects their result in a TestResult.
     *
     * @param \PHPUnit\Framework\TestResult $result
     * @return \PHPUnit\Framework\TestResult
     */
    public function run(?TestResult $result = null): TestResult
    {
        $permutations = [
            'Identifier Quoting' => function () {
                ConnectionManager::get('test')->getDriver()->enableAutoQuoting(true);
            },
//             'No identifier quoting' => function () {
//                 ConnectionManager::get('test')->getDriver()->enableAutoQuoting(false);
//             }
        ];

        foreach ($permutations as $permutation) {
            $permutation();
            $result = parent::run($result);
        }

        return $result;
    }
}
