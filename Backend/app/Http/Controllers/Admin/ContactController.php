<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ContactInfoRequest;
use App\Http\Requests\Admin\BulkActionRequest;
use App\Services\Admin\ContactService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function __construct(
        private ContactService $contactService
    ) {}

    /**
     * Display a listing of contact messages
     */
    public function messages(Request $request): JsonResponse
    {
        try {
            $filters = [
                'status' => $request->get('status'), // read, unread
                'search' => $request->get('search'),
                'date_from' => $request->get('date_from'),
                'date_to' => $request->get('date_to')
            ];

            $perPage = $request->get('per_page', 15);
            $messages = $this->contactService->getAllMessages($filters, $perPage);

            return response()->json([
                'success' => true,
                'data' => $messages
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified contact message
     */
    public function showMessage(int $id): JsonResponse
    {
        try {
            $message = $this->contactService->getMessageById($id);

            if (!$message) {
                return response()->json([
                    'success' => false,
                    'message' => 'Contact message not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $message
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark a message as read
     */
    public function markAsRead(int $id): JsonResponse
    {
        try {
            $message = $this->contactService->markMessageAsRead($id);

            if (!$message) {
                return response()->json([
                    'success' => false,
                    'message' => 'Contact message not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Message marked as read successfully',
                'data' => $message
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark a message as unread
     */
    public function markAsUnread(int $id): JsonResponse
    {
        try {
            $message = $this->contactService->markMessageAsUnread($id);

            if (!$message) {
                return response()->json([
                    'success' => false,
                    'message' => 'Contact message not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Message marked as unread successfully',
                'data' => $message
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a contact message
     */
    public function deleteMessage(int $id): JsonResponse
    {
        try {
            $deleted = $this->contactService->deleteMessage($id);

            if (!$deleted) {
                return response()->json([
                    'success' => false,
                    'message' => 'Contact message not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Message deleted successfully'
            ], 204);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Perform bulk actions on messages
     */
    public function bulkAction(BulkActionRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $result = $this->contactService->bulkAction($data['action'], $data['message_ids']);

            return response()->json([
                'success' => true,
                'message' => "Bulk {$data['action']} completed successfully",
                'data' => $result
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get unread messages count
     */
    public function unreadCount(): JsonResponse
    {
        try {
            $count = $this->contactService->getUnreadCount();

            return response()->json([
                'success' => true,
                'data' => [
                    'unread_count' => $count
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display contact information
     */
    public function info(): JsonResponse
    {
        try {
            $contactInfo = $this->contactService->getContactInfo();

            return response()->json([
                'success' => true,
                'data' => $contactInfo
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update contact information
     */
    public function updateInfo(ContactInfoRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $contactInfo = $this->contactService->updateContactInfo($data);

            return response()->json([
                'success' => true,
                'message' => 'Contact information updated successfully',
                'data' => $contactInfo
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
