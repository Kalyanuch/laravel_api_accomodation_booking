<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Booking API',
    description: 'Public REST API for asynchronous offer imports, property search and safe reservations. Authentication is not required.',
)]
#[OA\Server(url: '/', description: 'Current application')]
#[OA\Post(
    path: '/api/imports',
    operationId: 'createImport',
    summary: 'Submit an offers import',
    description: 'Validates and stores an idempotent supplier import, dispatches its processing job and immediately returns the pending import identifier.',
    security: [],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: 'object',
            required: ['supplier', 'external_import_id', 'sent_at', 'offers'],
            properties: [
                new OA\Property(property: 'supplier', type: 'string', example: 'supplier-a'),
                new OA\Property(property: 'external_import_id', type: 'string', example: 'import-2026-09-01-001'),
                new OA\Property(property: 'sent_at', type: 'string', format: 'date-time', example: '2026-09-01T10:00:00Z'),
                new OA\Property(
                    property: 'offers',
                    type: 'array',
                    minItems: 1,
                    items: new OA\Items(
                        type: 'object',
                        required: ['external_id', 'property', 'check_in', 'check_out', 'max_guests', 'price', 'currency', 'available_units', 'expires_at'],
                        properties: [
                            new OA\Property(property: 'external_id', type: 'string', example: 'offer-a-10001'),
                            new OA\Property(
                                property: 'property',
                                type: 'object',
                                required: ['code', 'name', 'city'],
                                properties: [
                                    new OA\Property(property: 'code', type: 'string', example: 'BCN-0001'),
                                    new OA\Property(property: 'name', type: 'string', example: 'Apartment near Sagrada Familia'),
                                    new OA\Property(property: 'city', type: 'string', example: 'Barcelona'),
                                ],
                            ),
                            new OA\Property(property: 'check_in', type: 'string', format: 'date', example: '2026-10-10'),
                            new OA\Property(property: 'check_out', type: 'string', format: 'date', example: '2026-10-15'),
                            new OA\Property(property: 'max_guests', type: 'integer', minimum: 1, example: 4),
                            new OA\Property(property: 'price', description: 'Price in the smallest currency unit.', type: 'integer', minimum: 0, example: 72500),
                            new OA\Property(property: 'currency', type: 'string', pattern: '^[A-Z]{3}$', example: 'EUR'),
                            new OA\Property(property: 'available_units', type: 'integer', minimum: 0, example: 2),
                            new OA\Property(property: 'expires_at', type: 'string', format: 'date-time', example: '2026-09-10T23:59:59Z'),
                        ],
                    ),
                ),
            ],
        ),
    ),
    tags: ['Imports'],
    responses: [
        new OA\Response(
            response: 202,
            description: 'Import accepted for asynchronous processing.',
            content: new OA\JsonContent(
                type: 'object',
                example: ['data' => ['id' => 15, 'status' => 'pending']],
            ),
        ),
        new OA\Response(response: 422, description: 'The request payload failed validation.'),
    ],
)]
#[OA\Get(
    path: '/api/imports/{import}',
    operationId: 'getImportStatus',
    summary: 'Get import status',
    description: 'Returns the current state and processing progress of an asynchronous import.',
    security: [],
    tags: ['Imports'],
    parameters: [
        new OA\Parameter(
            name: 'import',
            description: 'Import database identifier.',
            in: 'path',
            required: true,
            schema: new OA\Schema(type: 'integer', minimum: 1),
            example: 15,
        ),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Current import status.',
            content: new OA\JsonContent(
                type: 'object',
                example: ['data' => [
                    'id' => 15,
                    'supplier' => 'supplier-a',
                    'external_import_id' => 'import-2026-09-01-001',
                    'sent_at' => '2026-09-01T10:00:00Z',
                    'status' => 'completed',
                    'total_offers' => 20,
                    'processed_offers' => 20,
                    'error' => null,
                    'created_at' => '2026-09-01T10:00:02Z',
                    'completed_at' => '2026-09-01T10:00:04Z',
                ]],
            ),
        ),
        new OA\Response(response: 404, description: 'Import not found.'),
    ],
)]
#[OA\Get(
    path: '/api/properties',
    operationId: 'searchProperties',
    summary: 'Search properties with their best offer',
    description: 'Returns one cheapest active offer per property. Filtering, ranking, deterministic sorting and pagination are performed by the database.',
    security: [],
    tags: ['Properties'],
    parameters: [
        new OA\Parameter(name: 'check_in', description: 'Exact check-in date.', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'date'), example: '2026-10-10'),
        new OA\Parameter(name: 'check_out', description: 'Exact check-out date; must be after check-in.', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'date'), example: '2026-10-15'),
        new OA\Parameter(name: 'guests', description: 'Required guest capacity.', in: 'query', required: true, schema: new OA\Schema(type: 'integer', minimum: 1), example: 2),
        new OA\Parameter(name: 'city', description: 'Optional exact city filter.', in: 'query', required: false, schema: new OA\Schema(type: 'string', maxLength: 100), example: 'Barcelona'),
        new OA\Parameter(name: 'page', description: 'Pagination page.', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, default: 1), example: 1),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Paginated properties with the cheapest matching offer.',
            content: new OA\JsonContent(
                type: 'object',
                example: [
                    'data' => [[
                        'code' => 'BCN-0001',
                        'name' => 'Apartment near Sagrada Familia',
                        'city' => 'Barcelona',
                        'best_offer' => [
                            'id' => 125,
                            'supplier' => 'supplier-a',
                            'price' => 72500,
                            'currency' => 'EUR',
                            'available_units' => 2,
                            'expires_at' => '2026-09-10T23:59:59Z',
                        ],
                    ]],
                    'next' => null,
                    'prev' => null,
                    'per_page' => 10,
                ],
            ),
        ),
        new OA\Response(response: 422, description: 'The search parameters failed validation.'),
    ],
)]
#[OA\Post(
    path: '/api/offers/{offer}/reservations',
    operationId: 'createReservation',
    summary: 'Reserve an offer',
    description: 'Atomically creates a reservation and decrements availability. A database row lock prevents concurrent booking of the last unit.',
    security: [],
    tags: ['Reservations'],
    parameters: [
        new OA\Parameter(
            name: 'offer',
            description: 'Offer database identifier.',
            in: 'path',
            required: true,
            schema: new OA\Schema(type: 'integer', minimum: 1),
            example: 125,
        ),
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: 'object',
            required: ['client_reference', 'customer_name', 'customer_email'],
            properties: [
                new OA\Property(property: 'client_reference', type: 'string', maxLength: 255, example: 'web-order-9f782b1c'),
                new OA\Property(property: 'customer_name', type: 'string', maxLength: 255, example: 'John Smith'),
                new OA\Property(property: 'customer_email', type: 'string', format: 'email', maxLength: 255, example: 'john@example.com'),
            ],
        ),
    ),
    responses: [
        new OA\Response(
            response: 201,
            description: 'Reservation created.',
            content: new OA\JsonContent(
                type: 'object',
                example: ['data' => [
                    'id' => 31,
                    'offer_id' => 125,
                    'client_reference' => 'web-order-9f782b1c',
                    'customer_name' => 'John Smith',
                    'customer_email' => 'john@example.com',
                    'created_at' => '2026-09-08T12:00:00Z',
                ]],
            ),
        ),
        new OA\Response(response: 404, description: 'Offer not found.'),
        new OA\Response(response: 409, description: 'Offer is sold out or expired.'),
        new OA\Response(response: 422, description: 'The reservation payload failed validation.'),
    ],
)]
final class ApiDocumentation {}
