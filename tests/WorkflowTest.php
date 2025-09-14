<?php
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../acmda.php';

final class WorkflowTest extends TestCase
{
    private PDO $db;

    protected function setUp(): void
    {
        $this->db = initDb(':memory:');
        saveBusinessData($this->db, defaultServices());
    }

    public function testReceiveStoresPending(): void
    {
        $id = receiveMessage($this->db, '12345', 'Need door hanging');
        $stmt = $this->db->query('SELECT status, draft FROM wa_messages WHERE id = ' . (int)$id);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertSame('pending', $row['status']);
        $this->assertNotEmpty($row['draft']);
    }

    public function testApproveUpdatesStatus(): void
    {
        $id = receiveMessage($this->db, '12345', 'Need tiling');
        approveMessage($this->db, $id);
        $stmt = $this->db->query('SELECT status FROM wa_messages WHERE id = ' . (int)$id);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertSame('approved', $row['status']);
    }
}
