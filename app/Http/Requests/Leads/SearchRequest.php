<?php

namespace App\Http\Requests\Leads;

use Illuminate\Foundation\Http\FormRequest;

class SearchRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'search' => ['string', 'max:255'],
            'size' => ['integer', 'min:1'],
            'page' => ['integer', 'min:1'],
        ];
    }

    public function search(): string
    {
        return $this->validated('search', '');
    }

    public function perPage(): int
    {
        return $this->validated('size', 15);
    }

    public function page(): int
    {
        return $this->validated('page', 0);
    }
}
