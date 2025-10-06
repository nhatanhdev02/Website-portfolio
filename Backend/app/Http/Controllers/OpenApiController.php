<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="Laravel Admin Backend API",
 *     version="1.0.0",
 *     description="RESTful API for managing portfolio admin dashboard content with Clean Architecture implementation",
 *     @OA\Contact(
 *         email="admin@nhatanh.dev",
 *         name="Nhật Anh Dev"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 *
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="API Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="apiKey",
 *     in="header",
 *     name="Authorization",
 *     description="Enter token in format (Bearer <token>)"
 * )
 *
 * @OA\Tag(
 *     name="Authentication",
 *     description="Admin authentication endpoints"
 * )
 *
 * @OA\Tag(
 *     name="Hero Section",
 *     description="Hero section content management"
 * )
 *
 * @OA\Tag(
 *     name="About Section",
 *     description="About section content and image management"
 * )
 *
 * @OA\Tag(
 *     name="Services",
 *     description="Services CRUD operations and ordering"
 * )
 *
 * @OA\Tag(
 *     name="Projects",
 *     description="Portfolio projects management with image handling"
 * )
 *
 * @OA\Tag(
 *     name="Blog",
 *     description="Blog posts management with publishing workflow"
 * )
 *
 * @OA\Tag(
 *     name="Contact",
 *     description="Contact messages and info management"
 * )
 *
 * @OA\Tag(
 *     name="Settings",
 *     description="System settings and configuration management"
 * )
 */
class OpenApiController extends Controller
{
    // This class is only used for OpenAPI documentation annotations
}
