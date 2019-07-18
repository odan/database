<?php

declare(strict_types = 1);

namespace Odan\Database\Test;

use Odan\Database\Connection;
use PDO;

/**
 * @coversDefaultClass \Odan\Database\Connection
 */
class ConnectionTest extends BaseTest
{
    /**
     * Test create object.
     *
     * @return void
     */
    public function testInstance()
    {
        $connection = $this->getConnection();
        $this->assertInstanceOf(Connection::class, $connection);
    }

    /**
     * Test.
     */
    public function testPrepareQuery()
    {
        $select = $this->select();
        $select->columns('TABLE_NAME')
            ->from('information_schema.TABLES')
            ->where('TABLE_NAME', '=', 'TABLES');

        $statement = $select->prepare();

        $statement->execute();
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        $this->assertNotEmpty($row['TABLE_NAME']);
        $this->assertSame('TABLES', $row['TABLE_NAME']);
    }
}
