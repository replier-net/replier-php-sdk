<?php

namespace Replier;

/**
 * Replier.net WhatsApp API client.
 *
 *   $client = new \Replier\Client(['instanceId' => '123', 'token' => 'abc']);
 *   $client->sendMessage('15551234567', 'Hello!');
 */
class Client
{
    const VERSION = '1.0.0';
    const DEFAULT_BASE_URL = 'https://api.replier.net';

    private $instanceId;
    private $token;
    private $email;
    private $apiKey;
    private $baseUrl;
    private $timeout;

    /**
     * @param array $config instanceId, token, email, apiKey, baseUrl, timeout (seconds)
     */
    public function __construct(array $config = [])
    {
        $this->instanceId = isset($config['instanceId']) ? (string) $config['instanceId'] : null;
        $this->token = $config['token'] ?? null;
        $this->email = $config['email'] ?? null;
        $this->apiKey = $config['apiKey'] ?? null;
        $this->baseUrl = rtrim($config['baseUrl'] ?? self::DEFAULT_BASE_URL, '/');
        $this->timeout = (int) ($config['timeout'] ?? 70);
    }

    // ---------------------------------------------------------------- core

    /**
     * Call any instance endpoint. instanceid and token are added automatically.
     */
    public function call(string $method, array $params = []): array
    {
        $auth = [];
        if ($this->instanceId !== null) {
            $auth['instanceid'] = $this->instanceId;
        }
        if ($this->token !== null) {
            $auth['token'] = $this->token;
        }
        return $this->request($method, array_merge($auth, $params));
    }

    /**
     * Call an account endpoint. email and apikey are added automatically.
     */
    public function callAccount(string $method, array $params = []): array
    {
        $auth = [];
        if ($this->email !== null) {
            $auth['email'] = $this->email;
        }
        if ($this->apiKey !== null) {
            $auth['apikey'] = $this->apiKey;
        }
        return $this->request($method, array_merge($auth, $params));
    }

    private function request(string $method, array $params): array
    {
        $params = array_filter($params, function ($v) {
            return $v !== null;
        });

        $ch = curl_init($this->baseUrl . '/' . $method);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($params),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'User-Agent: replier-php/' . self::VERSION,
            ],
        ]);
        $raw = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            throw new ReplierException('Request failed: ' . $error);
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            throw new ReplierException('Invalid JSON response (HTTP ' . $status . ')', null, null, $status);
        }
        if ($status >= 400) {
            throw new ReplierException(self::reason($data, 'HTTP ' . $status), self::code($data), $data, $status);
        }
        if (array_key_exists('success', $data) && ($data['success'] === false || $data['success'] === 'false')) {
            throw new ReplierException(self::reason($data, 'Request failed'), self::code($data), $data, $status);
        }
        return $data;
    }

    private static function reason(array $data, string $fallback): string
    {
        if (!empty($data['reason'])) {
            return (string) $data['reason'];
        }
        if (!empty($data['msg'])) {
            return is_array($data['msg']) ? implode(', ', $data['msg']) : (string) $data['msg'];
        }
        return $fallback;
    }

    private static function code(array $data): ?string
    {
        return isset($data['errorCode']) ? (string) $data['errorCode'] : null;
    }

    private static function phones($phones): string
    {
        return is_array($phones) ? implode(',', $phones) : (string) $phones;
    }

    // ---------------------------------------------------------------- instance

    public function status(): array { return $this->call('status'); }
    public function qrCode(): array { return $this->call('qr_code'); }
    public function pairCode(string $phone): array { return $this->call('pair_code', ['phone' => $phone]); }
    public function logout(): array { return $this->call('logout'); }
    public function takeover(): array { return $this->call('takeover'); }
    public function expiry(): array { return $this->call('expiry'); }
    public function retry(): array { return $this->call('retry'); }
    public function reboot(): array { return $this->call('reboot'); }
    public function getSettings(): array { return $this->call('getSettings'); }
    public function setSettings(array $settings): array { return $this->call('settings', $settings); }
    public function me(): array { return $this->call('me'); }
    public function version(): array { return $this->call('version'); }
    public function screenshot(): array { return $this->call('screenshot'); }

    // ---------------------------------------------------------------- messages

    /**
     * @param array|null $options Extra send options forwarded to the instance.
     */
    public function sendMessage(string $phone, string $body, ?array $options = null): array
    {
        return $this->call('sendMessage', ['phone' => $phone, 'body' => $body, 'options' => $options]);
    }

    /**
     * @param string $body File URL or base64 data URI.
     */
    public function sendFile(string $phone, string $body, string $filename, ?string $caption = null): array
    {
        return $this->call('sendFile', ['phone' => $phone, 'body' => $body, 'filename' => $filename, 'caption' => $caption]);
    }

    public function sendPTT(string $phone, string $audio): array
    {
        return $this->call('sendPTT', ['phone' => $phone, 'audio' => $audio]);
    }

    public function sendLink(string $phone, string $body): array
    {
        return $this->call('sendLink', ['phone' => $phone, 'body' => $body]);
    }

    public function sendContact(string $phone, string $contactId): array
    {
        return $this->call('sendContact', ['phone' => $phone, 'contactId' => $contactId]);
    }

    public function sendLocation(string $phone, float $lat, float $lng, ?string $address = null): array
    {
        return $this->call('sendLocation', ['phone' => $phone, 'lat' => $lat, 'lng' => $lng, 'address' => $address]);
    }

    public function sendVCard(string $phone, string $vcard): array
    {
        return $this->call('sendVCard', ['phone' => $phone, 'vcard' => $vcard]);
    }

    public function sendButton(string $phone, string $body, array $buttons, ?string $header = null, ?string $footer = null): array
    {
        return $this->call('sendButton', ['phone' => $phone, 'body' => $body, 'buttons' => $buttons, 'header' => $header, 'footer' => $footer]);
    }

    public function sendList(string $phone, string $body, string $title, array $list, ?string $header = null, ?string $footer = null): array
    {
        return $this->call('sendList', ['phone' => $phone, 'body' => $body, 'title' => $title, 'list' => $list, 'header' => $header, 'footer' => $footer]);
    }

    public function forwardMessage(string $phone, string $messageId): array
    {
        return $this->call('forwardMessage', ['phone' => $phone, 'messageId' => $messageId]);
    }

    public function deleteMessage(string $phone, string $messageId): array
    {
        return $this->call('deleteMessage', ['phone' => $phone, 'messageId' => $messageId]);
    }

    public function sendSeen(string $phone): array
    {
        return $this->call('sendSeen', ['phone' => $phone]);
    }

    public function messages(string $phone, ?int $limit = null, ?int $lastMessageNumber = null): array
    {
        return $this->call('messages', ['phone' => $phone, 'limit' => $limit, 'lastMessageNumber' => $lastMessageNumber]);
    }

    public function showMessagesQueue(): array { return $this->call('showMessagesQueue'); }
    public function clearMessagesQueue(): array { return $this->call('clearMessagesQueue'); }
    public function showActionsQueue(): array { return $this->call('showActionsQueue'); }
    public function clearActionsQueue(): array { return $this->call('clearActionsQueue'); }

    // ---------------------------------------------------------------- chats

    public function dialogs(): array { return $this->call('dialogs'); }
    public function chats(): array { return $this->call('chats'); }
    public function contacts(): array { return $this->call('contacts'); }

    // ---------------------------------------------------------------- groups

    /** @param string|string[] $phones */
    public function createGroup(string $groupName, $phones): array
    {
        return $this->call('group', ['groupName' => $groupName, 'phones' => self::phones($phones)]);
    }

    public function leaveGroup(string $groupId): array
    {
        return $this->call('leaveGroup', ['groupId' => $groupId]);
    }

    /** @param string|string[] $phones */
    public function addGroupParticipant(string $groupId, $phones): array
    {
        return $this->call('addGroupParticipant', ['groupId' => $groupId, 'phones' => self::phones($phones)]);
    }

    /** @param string|string[] $phones */
    public function removeGroupParticipant(string $groupId, $phones): array
    {
        return $this->call('removeGroupParticipant', ['groupId' => $groupId, 'phones' => self::phones($phones)]);
    }

    /** @param string|string[] $phones */
    public function promoteGroupParticipant(string $groupId, $phones): array
    {
        return $this->call('promoteGroupParticipant', ['groupId' => $groupId, 'phones' => self::phones($phones)]);
    }

    /** @param string|string[] $phones */
    public function demoteGroupParticipant(string $groupId, $phones): array
    {
        return $this->call('demoteGroupParticipant', ['groupId' => $groupId, 'phones' => self::phones($phones)]);
    }

    // ---------------------------------------------------------------- channels

    public function createChannel(string $name, ?string $description = null, ?string $picture = null): array
    {
        return $this->call('channel', ['name' => $name, 'description' => $description, 'picture' => $picture]);
    }

    public function followChannel(string $channelId): array
    {
        return $this->call('channelFollow', ['channelId' => $channelId]);
    }

    public function unfollowChannel(string $channelId): array
    {
        return $this->call('channelUnfollow', ['channelId' => $channelId]);
    }

    public function channelInfo(string $channelId): array
    {
        return $this->call('channelInfo', ['channelId' => $channelId]);
    }

    // ---------------------------------------------------------------- webhook / bot / ban

    public function setWebhook(string $webhookUrl): array
    {
        return $this->call('webhook', ['webhookUrl' => $webhookUrl]);
    }

    public function getWebhookStatus(): array { return $this->call('getWebhookStatus'); }
    public function getBanSettings(): array { return $this->call('getBanSettings'); }
    public function botSettings(): array { return $this->call('botSettings'); }
    public function getBotSettings(): array { return $this->call('getbotSettings'); }

    // ---------------------------------------------------------------- account

    public function listInstances(): array
    {
        return $this->callAccount('listInstances');
    }

    public function newInstance(int $packageId, ?string $voucher = null): array
    {
        return $this->callAccount('newInstance', ['packageid' => $packageId, 'voucher' => $voucher]);
    }

    public function renewInstance(string $instanceId, int $packageId, ?string $voucher = null): array
    {
        return $this->callAccount('renewInstance', ['instanceid' => $instanceId, 'packageid' => $packageId, 'voucher' => $voucher]);
    }
}
