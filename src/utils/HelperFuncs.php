<?php

namespace UniPage\utils;

use Slim\Csrf\Guard;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

final class HelperFuncs
{
    public static function getCSRF($t)
    {
        // CSRF token name and value
        $csrfNameKey = $t->get(Guard::class)->getTokenNameKey();
        $csrfValueKey = $t->get(Guard::class)->getTokenValueKey();
        $csrfName = $t->get(Guard::class)->getTokenName();
        $csrfValue = $t->get(Guard::class)->getTokenValue();

        return [
            'keys' => [
                'name'  => $csrfNameKey,
                'value' => $csrfValueKey
            ],
            'name'  => $csrfName,
            'value' => $csrfValue
        ];
    }

    public static function UniJsonResponse($response, $statusCode, $content = [], $errorMessage = "")
    {
        $serializer = new Serializer([new ObjectNormalizer()], [new JsonEncoder()]);
        $response->getBody()->write($serializer->serialize(['error_message' => $errorMessage, 'content' => $content], 'json'));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($statusCode);
    }
}
