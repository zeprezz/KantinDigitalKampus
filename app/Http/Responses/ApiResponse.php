<?php

namespace App\Http\Responses;

use DOMDocument;
use Illuminate\Http\Request;
use SimpleXMLElement;

/**
 * Helper untuk membuat response API yang formatnya ditentukan secara dinamis
 * berdasarkan header Accept (application/json atau application/xml).
 * Semua endpoint (termasuk response error) memakai helper ini supaya
 * format response tidak tercampur antara JSON dan XML.
 */
class ApiResponse
{
    /**
     * Menentukan format response dari header Accept.
     * Return 'json', 'xml', atau null jika Accept tidak didukung.
     * Accept kosong atau *\/* dianggap JSON (default).
     */
    public static function detect(Request $request): ?string
    {
        $accept = strtolower(trim((string) $request->header('Accept', '')));

        if ($accept === '') {
            return 'json';
        }

        foreach (explode(',', $accept) as $part) {
            $type = trim(explode(';', $part)[0]);

            if ($type === 'application/json') {
                return 'json';
            }
            if ($type === 'application/xml') {
                return 'xml';
            }
            if ($type === '*/*' || $type === 'application/*') {
                return 'json';
            }
        }

        return null;
    }

    public static function success(Request $request, string $message, $data = null, int $status = 200, string $itemName = 'item')
    {
        return self::make($request, [
            'status' => 'success',
            'message' => $message,
            'data' => $data,
        ], $status, $itemName);
    }

    public static function error(Request $request, string $message, int $status, $errors = null, array $headers = [])
    {
        $body = [
            'status' => 'error',
            'message' => $message,
        ];

        if ($errors !== null) {
            $body['errors'] = $errors;
        }

        return self::make($request, $body, $status, 'item', $headers);
    }

    public static function make(Request $request, array $body, int $status = 200, string $itemName = 'item', array $headers = [])
    {
        $format = self::detect($request) ?? 'json';

        if ($format === 'xml') {
            $xml = new SimpleXMLElement('<response/>');
            self::arrayToXml($xml, $body, $itemName, $itemName);

            $dom = new DOMDocument('1.0', 'UTF-8');
            $dom->preserveWhiteSpace = false;
            $dom->formatOutput = true;
            $dom->loadXML($xml->asXML());

            return response($dom->saveXML(), $status, array_merge(
                ['Content-Type' => 'application/xml; charset=UTF-8'],
                $headers
            ));
        }

        return response()->json(
            $body,
            $status,
            $headers,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }

    private static function arrayToXml(SimpleXMLElement $node, array $data, string $dataItemName, string $itemName): void
    {
        foreach ($data as $key => $value) {
            $name = is_int($key) ? $itemName : preg_replace('/[^A-Za-z0-9_]/', '_', (string) $key);

            if (is_array($value)) {
                $child = $node->addChild($name);
                // Nama elemen untuk anggota list: data -> sesuai hint, orders -> order, lainnya -> message
                $childItemName = $key === 'data'
                    ? $dataItemName
                    : (['orders' => 'order', 'customers' => 'customer'][$key] ?? 'message');
                self::arrayToXml($child, $value, $dataItemName, $childItemName);
                continue;
            }

            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }

            if ($value === null) {
                $node->addChild($name);
                continue;
            }

            $node->addChild($name, htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8'));
        }
    }
}
