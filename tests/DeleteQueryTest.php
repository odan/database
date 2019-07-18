<?php

namespace Odan\Database\Test;

use Odan\Database\DeleteQuery;

/**
 * @coversDefaultClass \Odan\Database\DeleteQuery
 */
class DeleteQueryTest extends BaseTest
{
    /**
     * Test create object.
     *
     * @return void
     */
    public function testInstance()
    {
        $this->assertInstanceOf(DeleteQuery::class, $this->delete());
    }

    /**
     * @return DeleteQuery
     */
    protected function delete()
    {
        return new DeleteQuery($this->getConnection());
    }

    /**
     * Test.
     */
    public function testFrom()
    {
        $delete = $this->delete()->from('test');
        $this->assertSame('DELETE FROM `test`;', $delete->build());
        $this->assertTrue($delete->execute());
    }

    /**
     * Test.
     */
    public function testLowPriority()
    {
        $delete = $this->delete()->lowPriority()->from('test');
        $this->assertSame('DELETE LOW_PRIORITY FROM `test`;', $delete->build());
    }

    /**
     * Test.
     */
    public function testIgnore()
    {
        $delete = $this->delete()->ignore()->from('test')->where('id', '=', '1');
        $this->assertSame("DELETE IGNORE FROM `test` WHERE `id` = '1';", $delete->build());

        $delete = $this->delete()->lowPriority()->ignore()->from('test')->where('id', '=', '1');
        $this->assertSame("DELETE LOW_PRIORITY IGNORE FROM `test` WHERE `id` = '1';", $delete->build());
    }

    /**
     * Test.
     */
    public function testQuick()
    {
        $delete = $this->delete()->quick()->from('test')->where('id', '=', '1');
        $this->assertSame("DELETE QUICK FROM `test` WHERE `id` = '1';", $delete->build());
    }

    /**
     * Test.
     */
    public function testOrderBy()
    {
        $delete = $this->delete()->from('test')->where('id', '=', '1')->orderBy('id');
        $this->assertSame("DELETE FROM `test` WHERE `id` = '1' ORDER BY `id`;", $delete->build());

        $delete = $this->delete()->from('test')->where('id', '=', '1')->orderBy('id DESC');
        $this->assertSame("DELETE FROM `test` WHERE `id` = '1' ORDER BY `id` DESC;", $delete->build());

        $delete = $this->delete()->from('test')->where('id', '=', '1')->orderBy('test.id ASC');
        $this->assertSame("DELETE FROM `test` WHERE `id` = '1' ORDER BY `test`.`id` ASC;", $delete->build());

        $delete = $this->delete()->from('test')->where('id', '=', '1')->orderBy('db.test.id ASC');
        $this->assertSame("DELETE FROM `test` WHERE `id` = '1' ORDER BY `db`.`test`.`id` ASC;", $delete->build());
    }

    /**
     * Test.
     */
    public function testLimit()
    {
        $delete = $this->delete()->from('test')->where('id', '>', '1')->limit(10);
        $this->assertSame("DELETE FROM `test` WHERE `id` > '1' LIMIT 10;", $delete->build());
    }

    /**
     * Test.
     */
    public function testWhere()
    {
        $delete = $this->delete()->from('test')->where('id', '=', '1')
            ->where('test.id', '=', 1)
            ->orWhere('db.test.id', '>', 2);
        $this->assertSame("DELETE FROM `test` WHERE `id` = '1' AND `test`.`id` = '1' OR `db`.`test`.`id` > '2';", $delete->build());
    }

    /**
     * Test.
     */
    public function testTruncate()
    {
        $delete = $this->delete()->from('test')->truncate();
        $this->assertSame('TRUNCATE TABLE `test`;', $delete->build());
    }

    protected function setUp()
    {
        parent::setUp();
        $this->createTestTable();
    }
}
