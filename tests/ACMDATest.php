<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../acmda.php';
require_once __DIR__ . '/../wa_webhook.php';

class ACMDATest extends TestCase
{
    private array $services;

    protected function setUp(): void
    {
        $this->services = [
            'offers' => ['assembly', 'doors', 'locks', 'tiling', 'plumbing repairs'],
            'not_offered' => ['carpet fitting', 'electrical rewiring'],
            'policy' => 'Please use the online booking system for prices and availability.'
        ];
    }

    private function getDb(): PDO
    {
        $db = initDb(':memory:');
        saveBusinessData($db, $this->services);
        return $db;
    }

    public function testDraftReplySavesMemoryAndReturnsMessage(): void
    {
        $db = $this->getDb();
        $reply = draftReply($db, 'alice', 'Can you do assembly?');
        $this->assertSame('Yes, Ian can help with assembly. Please use the online booking system for prices and availability.', $reply);

        $mem = $db->query('SELECT user, message, response FROM memory')->fetchAll(PDO::FETCH_ASSOC);
        $this->assertCount(1, $mem);
        $this->assertSame('alice', $mem[0]['user']);
        $this->assertSame('Can you do assembly?', $mem[0]['message']);
    }

    public function testReceiveMessageStoresPendingWithDraft(): void
    {
        $db = $this->getDb();
        $id = receiveMessage($db, 'bob', 'Need help with doors');

        $stmt = $db->prepare('SELECT sender, message, draft, status FROM wa_messages WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertSame('bob', $row['sender']);
        $this->assertSame('Need help with doors', $row['message']);
        $this->assertSame('pending', $row['status']);
        $this->assertStringContainsString('Yes, Ian can help with doors.', $row['draft']);
    }

    public function testApprovalAndSendTransitions(): void
    {
        $db = $this->getDb();
        $id = receiveMessage($db, 'carol', 'locks needed');

        approveMessage($db, $id);
        $status = $db->query('SELECT status FROM wa_messages WHERE id = ' . $id)->fetchColumn();
        $this->assertSame('approved', $status);

        ob_start();
        sendApprovedMessages($db);
        $output = ob_get_clean();
        $this->assertStringContainsString('Sending to carol', $output);

        $status = $db->query('SELECT status FROM wa_messages WHERE id = ' . $id)->fetchColumn();
        $this->assertSame('sent', $status);
    }

    public function testWebhookVerification(): void
    {
        $db = $this->getDb();
        [$code, $body] = waHandleWebhook($db, 'GET', ['hub_verify_token' => 'tok', 'hub_challenge' => 'abc'], '', 'tok');
        $this->assertSame(200, $code);
        $this->assertSame('abc', $body);
    }

    public function testWebhookStoresIncomingMessage(): void
    {
        $db = $this->getDb();
        $json = json_encode([
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'messages' => [[
                            'from' => '123',
                            'text' => ['body' => 'hi there']
                        ]]
                    ]
                ]]
            ]]
        ]);
        waHandleWebhook($db, 'POST', [], $json, 'tok');
        $count = (int) $db->query('SELECT COUNT(*) FROM wa_messages')->fetchColumn();
        $this->assertSame(1, $count);
    }
}
