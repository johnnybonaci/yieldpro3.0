<?php

namespace App\Http\Resources\Leads;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CpaCollection extends ResourceCollection
{
    public $collects = CpaResource::class;
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */

    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
        ];
    }

    public function withResponse($request, $response)
    {
        $jsonResponse = json_decode($response->getContent(), true);
        $jsonResponse = array_merge($jsonResponse['meta'], $jsonResponse);
        $links = array_merge($jsonResponse['meta']['links'], $jsonResponse['links']);
        $jsonResponse['links'] = $links;
        unset($jsonResponse['meta']);
        $response->setContent(json_encode($jsonResponse));
    }
}
