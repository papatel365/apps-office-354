<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientSearchController extends Controller
{
    /**
     * Search clients for Select2 autocomplete.
     */
    public function search(Request $request): JsonResponse
    {
        $term = $request->get('q', '');

        $clients = Client::query()
            ->when($term, function ($query) use ($term) {
                $query->search($term);
            })
            ->active()
            ->select('id', 'name', 'client_number')
            ->limit(20)
            ->get();

        return response()->json([
            'data' => $clients->map(function ($client) {
                return [
                    'id' => $client->id,
                    'name' => $client->name . ' (' . $client->client_number . ')',
                    'client_number' => $client->client_number,
                ];
            })
        ]);
    }
}
