<?php

namespace Odan\Database\Test;

use Odan\Database\Schema;
use PDO;

/**
 * @coversDefaultClass \Odan\Database\Schema
 */
class SchemaTest extends BaseTest
{
    /**
     * Test create object.
     *
     * @return void
     */
    public function testInstance()
    {
        $schema = $this->getSchema();
        $this->assertInstanceOf(Schema::class, $schema);
    }

    /**
     * Test setDbName method.
     *
     * @return void
     */
    public function testSetDbName()
    {
        $schema = $this->getSchema();
        $dbName = $schema->getDatabase();
        if ($schema->existDatabase('test1')) {
            $schema->setDatabase('test1');
            $this->assertSame('test1', $schema->getDatabase());
        }

        $schema->setDatabase($dbName);
        $this->assertSame($dbName, $schema->getDatabase());

        $databases = $schema->getDatabases();
        $this->assertSame(true, in_array('information_schema', $databases));
        $this->assertSame(true, in_array('database_test', $databases));

        $databases = $schema->getDatabases('information_sch%');
        $this->assertSame(true, in_array('information_schema', $databases));
        $this->assertSame(1, count($databases));
    }

    /**
     * Test getTables method.
     *
     * @return void
     */
    public function testTables()
    {
        $db = $this->getConnection();
        $schema = $this->getSchema();

        if ($schema->existTable('test')) {
            $result = $schema->dropTable('test');
            $this->assertSame(true, $result);
        }

        $result = $schema->existTable('test');
        $this->assertSame(false, $result);

        $result = $schema->existTable('database_test.test_not_existing');
        $this->assertSame(false, $result);

        $result = $schema->existTable('notexistingdb.noexistingtable');
        $this->assertSame(false, $result);

        $result = $this->createTestTable();
        $this->assertSame(0, $result);

        $result = $schema->existTable('database_test.test');
        $this->assertSame(true, $result);

        $result = $schema->existTable('notexistingdb.noexistingtable');
        $this->assertSame(false, $result);

        $result = $schema->getTableSchemaId('test');
        $this->assertSame('567e34247e52e1ebec081130b34020384b0b7bbd', $result);

        $result = $schema->getTableSchemaId('database_test.test');
        $this->assertSame('567e34247e52e1ebec081130b34020384b0b7bbd', $result);

        $result = $schema->compareTableSchema('test', 'test');
        $this->assertSame(true, $result);

        $result = $schema->compareTableSchema('database_test.test', 'test');
        $this->assertSame(true, $result);

        $result = $schema->compareTableSchema('information_schema.tables', 'information_schema.tables');
        $this->assertSame(true, $result);

        $result = $schema->compareTableSchema('information_schema.tables', 'information_schema.views');
        $this->assertSame(false, $result);

        $tables = $schema->getTables();
        $this->assertSame([0 => 'test'], $tables);

        $tables = $schema->getTables('te%');
        $this->assertSame([0 => 'test'], $tables);

        $columns = $schema->getColumns('test');
        $this->assertSame(true, !empty($columns));
        $this->assertSame(10, count($columns));
        $this->assertSame('id', $columns[0]['column_name']);
        $this->assertSame('keyname', $columns[1]['column_name']);
        $this->assertSame('keyvalue', $columns[2]['column_name']);
        $this->assertSame('boolvalue', $columns[3]['column_name']);

        $columns = $schema->getColumns('database_test.test');
        $this->assertSame(true, !empty($columns));
        $this->assertSame(10, count($columns));

        $insert = $this->getConnection()->insert()->into('test')->set([
            'keyname' => 'test',
            'keyvalue' => '123',
        ]);
        $stmt = $insert->prepare();
        $stmt->execute();
        $this->assertTrue($stmt->rowCount() > 0);

        // With ON DUPLICATE KEY UPDATE, the affected-rows value per row
        // is 1 if the row is inserted as a new row, and 2 if an existing row is updated.
        // http://dev.mysql.com/doc/refman/5.0/en/insert-on-duplicate.html
        $insert = $db->insert()->into('test')->set([
            'id' => 1,
            'keyname' => 'test',
            'keyvalue' => '123',
            'boolvalue' => 1,
        ])->onDuplicateKeyUpdate([
            'id' => 1,
            'keyname' => 'testx',
            'keyvalue' => '123',
            'boolvalue' => 1,
        ]);
        $stmt = $insert->prepare();
        $stmt->execute();
        $result = $stmt->rowCount();
        $this->assertSame(2, $result);

        $result = $db->lastInsertId();
        $this->assertSame('1', $result);

        $result = $db->query('SELECT COUNT(*) AS count FROM `test`')->fetchAll(PDO::FETCH_ASSOC);
        $this->assertSame([0 => ['count' => '1']], $result);

        $result = $db->queryValue('SELECT COUNT(*) AS count FROM `test`', 'count');
        $this->assertSame('1', $result);

        $result = $db->queryValue('SELECT * FROM `test` WHERE id = 9999999;', 'id');
        $this->assertSame(null, $result);

        $rows = [
            0 => ['keyname' => 'test', 'keyvalue' => '123'],
            1 => ['keyname' => 'test2', 'keyvalue' => '1234'],
        ];
        $result = $db->insert()->into('test')->set($rows)->prepare();
        $result->execute();
        $this->assertSame(2, $result->rowCount());

        $result = $db->lastInsertId();
        $this->assertSame('2', $result);

        $result = $db->queryValue('SELECT COUNT(*) AS count FROM `test`', 'count');
        $this->assertSame('3', $result);

        $result = $schema->truncateTable('test');
        $this->assertSame(true, $result);

        $result = $db->queryValue('SELECT COUNT(*) AS count FROM `test`', 'count');
        $this->assertSame('0', $result);

        $result = $db->insert()->into('test')->set($rows)->prepare();
        $result->execute();
        $this->assertSame(2, $result->rowCount());

        $result = $db->queryValues('SELECT id,keyvalue FROM `test`', 'keyvalue');
        $this->assertSame(['123', '1234'], $result);

        $result = $db->queryMapColumn('SELECT id,keyname,keyvalue FROM `test`', 'keyname');
        $expected = [
            'test' => [
                'id' => '1',
                'keyname' => 'test',
                'keyvalue' => '123',
            ],
            'test2' => [
                'id' => '2',
                'keyname' => 'test2',
                'keyvalue' => '1234',
            ],
        ];
        $this->assertSame($expected, $result);

        $result = $schema->clearTable('test');
        $this->assertSame(true, $result);

        $result = $db->queryValue('SELECT COUNT(*) AS count FROM `test`', 'count');
        $this->assertSame('0', $result);

        $result = $db->queryValue("SHOW TABLE STATUS FROM `database_test` LIKE 'test'; ", 'Auto_increment');
        $this->assertSame('3', $result);

        $rows = [];
        for ($i = 0; $i < 100; $i++) {
            $rows[] = ['keyname' => 'test', 'keyvalue' => 'value-' . $i];
        }
        $result = $db->insert()->into('test')->set($rows)->prepare();
        $result->execute();
        $this->assertSame(100, $result->rowCount());

        $result = $db->query('SELECT keyname,keyvalue FROM test;')->fetchAll(PDO::FETCH_ASSOC);
        $this->assertSame(true, $rows == $result);

        $fields = [
            'keyname' => 'test-new',
            'keyvalue' => 'value-new',
        ];
        $stmt = $db->update()->table('test')->set($fields)->where('keyname', '=', 'test')->prepare();
        $stmt->execute();
        $this->assertSame(100, $stmt->rowCount());

        $stmt = $db->delete()->from('test')->where('id', '=', '10')->prepare();
        $stmt->execute();
        $this->assertSame(1, $stmt->rowCount());

        $stmt = $db->delete()->from('test')->where('id', '=', '9999999')->prepare();
        $stmt->execute();
        $this->assertSame(0, $stmt->rowCount());

        $result = $schema->renameTable('test', 'temp');
        $this->assertSame(true, $result);

        $result = $schema->renameTable('temp', 'test');
        $this->assertSame(true, $result);

        $result = $schema->copyTable('test', 'test_copy');
        $this->assertSame(true, $result);

        $result = $schema->existTable('test_copy');
        $this->assertSame(true, $result);

        $schema->dropTable('test_copy');

        // With data
        $result = $schema->copyTable('test', 'test_copy');
        $this->assertSame(true, $result);

        $result = $schema->existTable('test_copy');
        $this->assertSame(true, $result);

        $schema->dropTable('test_copy');
    }

    /**
     * Test getTables method.
     *
     * @return void
     */
    public function testGetColumnNames()
    {
        $schema = $this->getSchema();
        $result = $schema->getColumnNames('test');
        $this->assertTrue(isset($result['id']));
        $this->assertTrue(isset($result['keyname']));
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testRenameTable()
    {
        $schema = $this->getSchema();
        $this->assertTrue($schema->renameTable('test', 'test_copy'));
        $this->assertTrue($schema->existTable('test_copy'));
        $this->assertFalse($schema->existTable('test'));
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testCopyTable()
    {
        $schema = $this->getSchema();
        $this->assertTrue($schema->copyTable('test', 'test_copy'));
        $this->assertTrue($schema->existTable('test_copy'));
        $this->assertTrue($schema->existTable('test'));
    }

    protected function setUp()
    {
        parent::setUp();
        $this->createTestTable();
    }
}
