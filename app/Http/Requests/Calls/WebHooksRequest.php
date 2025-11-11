<?php

namespace App\Http\Requests\Calls;

use Illuminate\Http\JsonResponse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\ValidationRule;

class WebHooksRequest extends FormRequest
{
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
            'data.*.caller_number' => 'required|string',
            'data.*.id' => 'required|string',
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
            'data.*.caller_number' => 'Phone is required',
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
}
