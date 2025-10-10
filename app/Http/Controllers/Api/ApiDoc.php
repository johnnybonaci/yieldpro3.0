<?php

namespace App\Http\Controllers\Api;

use App\Models\Leads\Lead;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

/**
 * @OA\Info(
 *             title="YIELDPRO API",
 *             version="1.0",
 *             description="## Last update 2023.02.22"
 * )
 *
 **/
class ApiDoc extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/v1/token",
     *     security={{"basic": {}}},
     *     tags={"Authentication API"},
     *     summary="Logging & get access_token",
     *     description="Step 1 - Request API Token by asking your account rep.",
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *              @OA\Property(property="status", type="string", example="success"),
     *              @OA\Property(property="message", type="string", example="Data has been saved successfully"),
     *              @OA\Property(property="access_token", type="string", example="MDUxMzg1MH0.vKG7su08iiKHumCeHl6ftBgg1xQrPcWTQuDLA1ZO6IQ"),
     *              @OA\Property(property="token_type", type="string", example="Basic"),
     *              @OA\Property(property="expires_in", type="string", example="86400"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error: Parameter is invalid",
     *         @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Parameter is invalid.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Error: access_token is invalid",
     *         @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Error: access_token has expired",
     *         @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Error: request url is error",
     *         @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="request url is error.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error: internal server error",
     *         @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="internal server error returned if another unexpected error occurs.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=502,
     *         description="Error: api_token is invalid",
     *         @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="api_token is invalid")
     *         )
     *     )
     * )
     *
     * @OA\Post(
     *     security={{"bearer": {}}},
     *     path="/api/v1/leads/data",
     *     tags={"LEADS"},
     *     summary="Create a Leads Data",
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="data",
     *                     type="array",
     *                     @OA\Items(
     *                          required={"firstName", "lastName", "phone"},
     *                          @OA\Property(
     *                              property="type",
     *                              type="string",
     *                              description="lead type",
     *                              example="legal"
     *                          ),
     *                          @OA\Property(
     *                              property="email",
     *                              type="email",
     *                              description="email from lead",
     *                              example="johnconnor@mail.com"
     *                          ),
     *                          @OA\Property(
     *                              property="firstName",
     *                              type="string",
     *                              description="first name of lead",
     *                              example="John"
     *                          ),
     *                          @OA\Property(
     *                              property="lastName",
     *                              type="string",
     *                              description="last name of lead",
     *                              example="Connor"
     *                          ),
     *                          @OA\Property(
     *                              property="phone",
     *                              type="string",
     *                              description="phone of lead",
     *                              example="4789793792"
     *                          ),
     *                          @OA\Property(
     *                              property="state",
     *                              type="string",
     *                              description="state of lead",
     *                              example="CA"
     *                          ),
     *                          @OA\Property(
     *                              property="zip_code",
     *                              type="string",
     *                              description="zip code",
     *                              example="30728"
     *                          ),
     *                          @OA\Property(
     *                              property="sub_ID",
     *                              type="string",
     *                              description="Tracking code",
     *                              example="127"
     *                          ),
     *                          @OA\Property(
     *                              property="pub_ID",
     *                              type="string",
     *                              description="Tracking code",
     *                              example="2101"
     *                          ),
     *                          @OA\Property(
     *                              property="cpl",
     *                              type="decimal",
     *                              description="Costs per leads",
     *                              example="3.21"
     *                          ),
     *                          @OA\Property(
     *                              property="campaign_name",
     *                              type="string",
     *                              description="Campaign Name",
     *                              example="Internal MXA - 3456"
     *                          ),
     *                          @OA\Property(
     *                              property="ip",
     *                              type="string",
     *                              description="IP client",
     *                              example="21.56.125.244"
     *                          ),
     *                          @OA\Property(
     *                              property="lead_id",
     *                              type="string",
     *                              description="Lead ID",
     *                              example="123ab45678"
     *                          ),
     *                          @OA\Property(
     *                              property="partners",
     *                              type="string",
     *                              description="Client",
     *                              example="Money Makers Inc"
     *                          ),
     *                          @OA\Property(
     *                              property="lead_price",
     *                              type="decimal",
     *                              description="Revenue of lead",
     *                              example="12.34"
     *                          ),
     *                          @OA\Property(
     *                              property="lead_durations",
     *                              type="string",
     *                              description="Calls duration",
     *                              example="23min"
     *                          ),
     *                          @OA\Property(
     *                              property="lead_date_created",
     *                              type="date (Y-m-d H:m:s)",
     *                              description="Calls date",
     *                              example="2023-01-20 09:04:07"
     *                          ),
     *                          @OA\Property(
     *                              property="terminating_phone",
     *                              type="string",
     *                              description="Terminating phone of lead",
     *                              example="9138457540"
     *                          ),
     *                          @OA\Property(
     *                              property="data",
     *                              ref="#/components/schemas/ExtraData"
     *                          )
     *                      )
     *                  )
     *               )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *              @OA\Property(property="status", type="string", example="success"),
     *              @OA\Property(property="message", type="string", example="Data has been saved successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error: Parameter is invalid",
     *         @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Parameter is invalid.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Error: access_token is invalid",
     *         @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Error: access_token has expired",
     *         @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Error: request url is error",
     *         @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="request url is error.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error: Unprocessable Content",
     *         @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="The given data was invalid."),
     *              @OA\Property(property="errors", type="string", example="The data field is required.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error: internal server error",
     *         @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="internal server error returned if another unexpected error occurs.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=502,
     *         description="Error: api_token is invalid",
     *         @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="api_token is invalid")
     *         )
     *     )
     * )
     *
     * @OA\Post(
     *     security={{"bearerAuth": {}}},
     *     path="/api/v1/leads/update",
     *     tags={"LEADS"},
     *     summary="Update Leads Data",
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="data",
     *                     type="array",
     *                     @OA\Items(
     *                          required={"phone", "lead_status", "lead_price", "lead_durations", "cpl"},
     *                          @OA\Property(
     *                              property="phone",
     *                              type="string",
     *                              description="phone of lead",
     *                              example="4789793792"
     *                          ),
     *                          @OA\Property(
     *                              property="state",
     *                              type="string",
     *                              description="state of lead",
     *                              example="CA"
     *                          ),
     *                          @OA\Property(
     *                              property="campaign_name",
     *                              type="string",
     *                              description="Campaign Name",
     *                              example="Internal MXA - 3456"
     *                          ),
     *                          @OA\Property(
     *                              property="lead_date_created",
     *                              type="date (Y-m-d H:m:s)",
     *                              description="Calls date",
     *                              example="2023-01-20 09:04:07"
     *                          ),
     *                          @OA\Property(
     *                              property="lead_status",
     *                              type="string",
     *                              description="Status lead",
     *                              example="Declined"
     *                          ),
     *                          @OA\Property(
     *                              property="partners",
     *                              type="string",
     *                              description="Client",
     *                              example="Money Makers Inc"
     *                          ),
     *                          @OA\Property(
     *                              property="partner_id",
     *                              type="string",
     *                              description="Client ID",
     *                              example="1234"
     *                          ),
     *                          @OA\Property(
     *                              property="lead_price",
     *                              type="decimal",
     *                              description="Revenue of lead",
     *                              example="12.34"
     *                          ),
     *                          @OA\Property(
     *                              property="lead_durations",
     *                              type="string",
     *                              description="Calls duration",
     *                              example="23min"
     *                          ),
     *                          @OA\Property(
     *                              property="terminating_phone",
     *                              type="string",
     *                              description="Terminating phone of lead",
     *                              example="9138457540"
     *                          ),
     *                          @OA\Property(
     *                              property="cpl",
     *                              type="decimal",
     *                              description="Costs per leads",
     *                              example="3.21"
     *                          )
     *                      )
     *                  )
     *               )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *              @OA\Property(property="status", type="string", example="success"),
     *              @OA\Property(property="message", type="string", example="Data has been saved successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error: Parameter is invalid",
     *         @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Parameter is invalid.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Error: access_token is invalid",
     *         @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Error: access_token has expired",
     *         @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Error: request url is error",
     *         @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="request url is error.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error: Unprocessable Content",
     *         @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="The given data was invalid."),
     *              @OA\Property(property="errors", type="string", example="The data field is required.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error: internal server error",
     *         @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="internal server error returned if another unexpected error occurs.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=502,
     *         description="Error: api_token is invalid",
     *         @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="api_token is invalid")
     *         )
     *     )
     * )
     */
    public function index()
    {
    }

    /**
     * @OA\Schema(
     *     schema="ExtraData",
     *     title="Sample schema for extra data",
     * 	   @OA\Property(
     *         property="dataField1",
     *         type="string",
     *         example="abcd"
     *     ),
     * 	   @OA\Property(
     *         property="dataField2",
     *         type="string",
     *         example="efgh"
     *     ),
     * 	   @OA\Property(
     *         property="dataField3",
     *         type="string",
     *         example="ijkl"
     *     )
     * )
     */
    public function ExtraDataSchema()
    {
    }
}
