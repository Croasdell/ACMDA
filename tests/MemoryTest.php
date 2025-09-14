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
}
