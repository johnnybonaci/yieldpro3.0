<?php

namespace App\Http\Requests\Leads;

use App\Rules\ValidUSPhone;
use Illuminate\Http\JsonResponse;
use App\Services\Leads\ValidatedService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\ValidationRule;

class LeadApiRequest extends FormRequest
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
            'data' => 'required|array',
            'data.*.phone' => ['required', new ValidUSPhone()],
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
        $leads = collect($this->all()['data'])->map(function ($datos) {
            $this->validated_service->validateEmail($datos);
            $this->validated_service->validatePhone($datos);
            $this->validated_service->validatePub($datos);
            $this->validated_service->validateSub($datos);
            $this->validated_service->validateMetrics($datos);

            return $datos;
        })->toArray();

        $this->replace($leads);
    }
}
