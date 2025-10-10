<?php

namespace App\Http\Requests\Leads;

use Illuminate\Http\JsonResponse;
use App\Services\Leads\ValidatedService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\ValidationRule;

class LeadBotRequest extends FormRequest
{
    public function __construct(private ValidatedService $validated_service)
    {
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'phone' => 'required|numeric|digits_between:10,11',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.required' => 'Phone is required',
            'phone.numeric' => 'Phone must be numeric',
            'phone.digits_between' => 'Phone must be between 10 and 11 digits',
        ];
    }

    /**
     * Summary of failedValidation.
     *
     * @throws ValidationException
     */
    public function failedValidation(Validator $validator): void
    {
        $response = [
            'status' => 'errors',
            'message' => 'The given data was invalid.',
            'errors' => $validator->errors(),
        ];

        throw new ValidationException($validator, response()->json($response, JsonResponse::HTTP_UNPROCESSABLE_ENTITY));
    }

    /**
     * Summary of passedValidation.
     */
    protected function passedValidation(): void
    {
        $datos = $this->all();
        $this->prepareData($datos);

        $this->validated_service->validatePhone($datos);
        $this->validated_service->validateEmail($datos);
        $this->validated_service->validatePubWithoutUser($datos, 2);
        $datos['pub_ID'] = $datos['pub_id'];
        $this->validated_service->validateSub($datos);
        $this->validated_service->validateMetrics($datos);
        $this->replace($datos);
    }

    private function prepareData(array &$datos): array
    {
        $type = [
            1 => 'ACA',
            2 => 'MC',
            3 => 'MC_IB',
            4 => 'AC_IB',
        ];
        $datos['type'] = $datos['type'] ?? 1;
        $datos['type'] = $type[$datos['type']] ?? 'ACA';
        $datos['pub_id'] = $datos['pub_ID'];
        $datos['email'] = $datos['email'] ?? $datos['firstName'] . '_' . $datos['lastName'] . '@gmail.com';

        return $datos;
    }
}
