<?php



namespace App\Response;

use Symfony\Component\HttpFoundation\JsonResponse;

class CustomJsonResponse extends JsonResponse
{
    public function __construct($data = [], int $status = 200, string $message)
    {
        // Préparer la structure de la réponse
        $response = [
            'success' => ($status === 200 || $status === 201),
            'message' =>   $message,
            'content' => $data,
        ];

        parent::__construct($response, $status);
    }
}
