<?php
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../mem.php';

final class MemoryTest extends TestCase
{
    public function testBusinessDataRoundTrip(): void
    {
        $db = new PDO('sqlite::memory:');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        initBusiness($db);
        $data = [
            'offers' => ['tiling', 'doors'],
            'not_offered' => ['carpets'],
            'policy' => 'Contact via website for bookings.'
        ];
        saveBusinessData($db, $data);
        $loaded = loadBusinessData($db);
        $this->assertEquals($data, $loaded);
    }

    public function testConversationMemory(): void
    {
        $db = new PDO('sqlite::memory:');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        initMemory($db);
        mem_save($db, 'tester', 'user', 'Hi');
        mem_save($db, 'tester', 'assistant', 'Hello');
        $history = mem_history($db, 'tester');
        $this->assertSame([
            ['role' => 'user', 'content' => 'Hi'],
            ['role' => 'assistant', 'content' => 'Hello']
        ], $history);
        mem_clear($db, 'tester');
        $this->assertSame([], mem_history($db, 'tester'));
    }
}
