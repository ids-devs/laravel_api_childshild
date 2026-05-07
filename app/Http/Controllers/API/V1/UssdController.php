<?php

namespace App\Http\Controllers\API\V1;

use App\Services\UssdService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * @group USSD
 * Endpoint for Africa's Talking USSD gateway (*123#).
 */
class UssdController extends BaseController
{
    public function __construct(private UssdService $ussd) {}

    /**
     * Handle USSD request from Africa's Talking
     *
     * This endpoint is called by Africa's Talking on every USSD interaction.
     * Returns CON (continue) or END (end session) prefixed plain text.
     *
     * @bodyParam sessionId string required AT session ID. Example: ATUid_xyz
     * @bodyParam serviceCode string required USSD service code. Example: *123#
     * @bodyParam phoneNumber string required Caller phone number. Example: +258849123456
     * @bodyParam text string required Concatenated user input. Example: 1*2
     *
     * @response plain text "CON Welcome to ChildShield\n1. Register family..."
     */
    public function handle(Request $request): Response
    {
        $response = $this->ussd->handle(
            sessionId:   $request->input('sessionId'),
            serviceCode: $request->input('serviceCode'),
            phoneNumber: $request->input('phoneNumber'),
            text:        $request->input('text', ''),
        );

        return response($response, 200)->header('Content-Type', 'text/plain');
    }
}
