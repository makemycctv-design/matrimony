<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\View;

/**
 * Serves the OpenAPI 3 specification and a Swagger UI documentation page for
 * the versioned REST API.
 */
class ApiDocsController extends Controller
{
    public function ui(): \Illuminate\Contracts\View\View
    {
        return View::make('api-docs');
    }

    public function spec(): JsonResponse
    {
        $json = ['type' => 'object'];
        $envelope = fn (array $props) => ['type' => 'object', 'properties' => ['data' => ['type' => 'object', 'properties' => $props]]];
        $bearer = [['bearerAuth' => []]];

        return response()->json([
            'openapi' => '3.0.3',
            'info' => [
                'title' => config('app.name').' API',
                'version' => '1.0.0',
                'description' => 'Versioned REST API for the matrimony platform. Authenticate with a Sanctum bearer token obtained from /auth/login. Responses use a `{ "data": ... }` envelope.',
            ],
            'servers' => [['url' => url('/api/v1'), 'description' => 'v1']],
            'components' => [
                'securitySchemes' => [
                    'bearerAuth' => ['type' => 'http', 'scheme' => 'bearer'],
                ],
                'schemas' => [
                    'AuthResponse' => $envelope([
                        'user' => ['type' => 'object'],
                        'token' => ['type' => 'string'],
                    ]),
                    'Message' => ['type' => 'object', 'properties' => ['message' => ['type' => 'string']]],
                ],
            ],
            'paths' => [
                '/auth/register' => ['post' => $this->op('Register a new member', 'Auth', body: [
                    'name' => 'string', 'email' => 'string', 'mobile' => 'string', 'gender' => 'string',
                    'password' => 'string', 'password_confirmation' => 'string', 'accept_terms' => 'boolean', 'accept_privacy' => 'boolean',
                ], responseRef: 'AuthResponse')],
                '/auth/login' => ['post' => $this->op('Login and receive a token', 'Auth', body: [
                    'login' => 'string', 'password' => 'string', 'device_name' => 'string',
                ], responseRef: 'AuthResponse')],
                '/auth/me' => ['get' => $this->op('Get the authenticated user', 'Auth', security: $bearer)],
                '/auth/logout' => ['post' => $this->op('Revoke the current token', 'Auth', security: $bearer)],
                '/profile' => ['get' => $this->op('Get my profile', 'Profile', security: $bearer)],
                '/profile/section/{section}' => ['put' => $this->op('Update a profile section', 'Profile', security: $bearer, params: ['section'])],
                '/search' => ['get' => $this->op('Search profiles with filters', 'Discovery', security: $bearer)],
                '/matches' => ['get' => $this->op('Recommended matches', 'Discovery', security: $bearer)],
                '/profiles/{profile}' => ['get' => $this->op('Get a profile (visibility-masked)', 'Discovery', security: $bearer, params: ['profile'])],
                '/interests' => [
                    'get' => $this->op('List sent & received interests', 'Interests', security: $bearer),
                    'post' => $this->op('Send an interest', 'Interests', security: $bearer, body: ['profile' => 'string', 'message' => 'string']),
                ],
                '/interests/{interest}/accept' => ['post' => $this->op('Accept an interest', 'Interests', security: $bearer, params: ['interest'])],
                '/interests/{interest}/decline' => ['post' => $this->op('Decline an interest', 'Interests', security: $bearer, params: ['interest'])],
                '/interests/{interest}/withdraw' => ['post' => $this->op('Withdraw an interest', 'Interests', security: $bearer, params: ['interest'])],
                '/plans' => ['get' => $this->op('List active subscription plans', 'Subscription')],
                '/subscription' => ['get' => $this->op('My subscription & entitlements', 'Subscription', security: $bearer)],
                '/notifications' => ['get' => $this->op('List notifications', 'Notifications', security: $bearer)],
                '/notifications/unread-count' => ['get' => $this->op('Unread notification count', 'Notifications', security: $bearer)],
                '/notifications/{id}/read' => ['post' => $this->op('Mark a notification read', 'Notifications', security: $bearer, params: ['id'])],
            ],
        ], 200, [], JSON_UNESCAPED_SLASHES);
    }

    /**
     * @param  array<string, string>  $body
     * @param  list<string>  $params
     * @param  list<array<string, mixed>>  $security
     */
    private function op(string $summary, string $tag, array $body = [], array $params = [], array $security = [], ?string $responseRef = null): array
    {
        $op = ['summary' => $summary, 'tags' => [$tag], 'responses' => [
            '200' => ['description' => 'Success', 'content' => ['application/json' => ['schema' => $responseRef ? ['$ref' => '#/components/schemas/'.$responseRef] : ['type' => 'object']]]],
            '422' => ['description' => 'Validation error'],
        ]];

        if ($security) {
            $op['security'] = $security;
        }

        if ($params) {
            $op['parameters'] = array_map(fn ($p) => [
                'name' => $p, 'in' => 'path', 'required' => true, 'schema' => ['type' => 'string'],
            ], $params);
        }

        if ($body) {
            $op['requestBody'] = ['content' => ['application/json' => ['schema' => [
                'type' => 'object',
                'properties' => array_map(fn ($t) => ['type' => $t], $body),
            ]]]];
        }

        return $op;
    }
}
