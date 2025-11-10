<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\CaptchaException;
use App\Services\DetranCaptchaClient;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response;

class CaptchaController extends Controller
{
    public function __construct(private readonly DetranCaptchaClient $captchaClient)
    {
    }

    /**
     * Retrieve the captcha image from the external service and return it as a binary response.
     */
    public function __invoke(Request $request): Response
    {
        try {
            $captcha = $this->captchaClient->fetch();

            return response($captcha['body'], Response::HTTP_OK)
                ->header('Content-Type', $captcha['content_type']);
        } catch (CaptchaException $exception) {
            return response()->json(
                ['message' => $exception->getMessage()],
                $exception->statusCode
            );
        }
    }
}
