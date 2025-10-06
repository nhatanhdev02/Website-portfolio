<?php

namespace App\Http\Controllers;

/**
 * @OA\Schema(
 *     schema="ErrorResponse",
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="message", type="string", example="Error message")
 * )
 *
 * @OA\Schema(
 *     schema="ValidationErrorResponse",
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="message", type="string", example="The given data was invalid."),
 *     @OA\Property(
 *         property="errors",
 *         type="object",
 *         @OA\AdditionalProperties(
 *             type="array",
 *             @OA\Items(type="string")
 *         )
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="UnauthorizedResponse",
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="message", type="string", example="Unauthenticated")
 * )
 *
 * @OA\Schema(
 *     schema="NotFoundResponse",
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="message", type="string", example="Resource not found")
 * )
 *
 * @OA\Schema(
 *     schema="SuccessResponse",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Operation completed successfully")
 * )
 *
 * @OA\Schema(
 *     schema="PaginationMeta",
 *     @OA\Property(property="current_page", type="integer", example=1),
 *     @OA\Property(property="per_page", type="integer", example=15),
 *     @OA\Property(property="total", type="integer", example=100),
 *     @OA\Property(property="last_page", type="integer", example=7),
 *     @OA\Property(property="from", type="integer", example=1),
 *     @OA\Property(property="to", type="integer", example=15)
 * )
 *
 * @OA\Schema(
 *     schema="AdminUser",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="username", type="string", example="admin"),
 *     @OA\Property(property="email", type="string", example="admin@example.com"),
 *     @OA\Property(property="last_login_at", type="string", format="date-time", example="2024-01-01T12:00:00Z"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T10:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T12:00:00Z")
 * )
 *
 * @OA\Schema(
 *     schema="Hero",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="greeting_vi", type="string", example="Xin chào, tôi là"),
 *     @OA\Property(property="greeting_en", type="string", example="Hello, I'm"),
 *     @OA\Property(property="name", type="string", example="Nhật Anh"),
 *     @OA\Property(property="title_vi", type="string", example="Lập trình viên Fullstack"),
 *     @OA\Property(property="title_en", type="string", example="Fullstack Developer"),
 *     @OA\Property(property="subtitle_vi", type="string", example="Chuyên về phát triển web hiện đại"),
 *     @OA\Property(property="subtitle_en", type="string", example="Specialized in modern web development"),
 *     @OA\Property(property="cta_text_vi", type="string", example="Xem dự án"),
 *     @OA\Property(property="cta_text_en", type="string", example="View Projects"),
 *     @OA\Property(property="cta_link", type="string", example="#projects"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Service",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="title_vi", type="string", example="Phát triển Web"),
 *     @OA\Property(property="title_en", type="string", example="Web Development"),
 *     @OA\Property(property="description_vi", type="string", example="Phát triển ứng dụng web hiện đại"),
 *     @OA\Property(property="description_en", type="string", example="Modern web application development"),
 *     @OA\Property(property="icon", type="string", example="web-icon"),
 *     @OA\Property(property="color", type="string", example="#FF6B6B"),
 *     @OA\Property(property="bg_color", type="string", example="#FFE5E5"),
 *     @OA\Property(property="order", type="integer", example=1),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Project",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="title_vi", type="string", example="Ứng dụng E-commerce"),
 *     @OA\Property(property="title_en", type="string", example="E-commerce Application"),
 *     @OA\Property(property="description_vi", type="string", example="Ứng dụng thương mại điện tử hiện đại"),
 *     @OA\Property(property="description_en", type="string", example="Modern e-commerce application"),
 *     @OA\Property(property="image", type="string", example="https://example.com/project1.jpg"),
 *     @OA\Property(property="link", type="string", example="https://project1.com"),
 *     @OA\Property(property="technologies", type="array", @OA\Items(type="string", example="Laravel")),
 *     @OA\Property(property="category", type="string", example="web"),
 *     @OA\Property(property="featured", type="boolean", example=true),
 *     @OA\Property(property="order", type="integer", example=1),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="BlogPost",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="title_vi", type="string", example="Hướng dẫn Laravel"),
 *     @OA\Property(property="title_en", type="string", example="Laravel Tutorial"),
 *     @OA\Property(property="content_vi", type="string", example="Nội dung bài viết bằng tiếng Việt..."),
 *     @OA\Property(property="content_en", type="string", example="Article content in English..."),
 *     @OA\Property(property="excerpt_vi", type="string", example="Tóm tắt bài viết..."),
 *     @OA\Property(property="excerpt_en", type="string", example="Article excerpt..."),
 *     @OA\Property(property="thumbnail", type="string", example="https://example.com/blog1.jpg"),
 *     @OA\Property(property="status", type="string", enum={"draft", "published"}, example="published"),
 *     @OA\Property(property="published_at", type="string", format="date-time"),
 *     @OA\Property(property="tags", type="array", @OA\Items(type="string", example="Laravel")),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="ContactMessage",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="John Doe"),
 *     @OA\Property(property="email", type="string", example="john@example.com"),
 *     @OA\Property(property="message", type="string", example="Hello, I would like to discuss a project..."),
 *     @OA\Property(property="read_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class OpenApiSchemas
{
    // This class is only used for OpenAPI schema definitions
}
