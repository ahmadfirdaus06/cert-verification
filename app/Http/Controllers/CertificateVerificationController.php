<?php

namespace App\Http\Controllers;

use App\Models\VerificationResult;
use App\Services\VerificationService;
use App\VerificationResults;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rules\File;

class CertificateVerificationController extends Controller
{
    public function __construct(
        protected VerificationService $service,
    ) {}

    public function verify(Request $request)
    {
        $request->validate([
            'certificate'  => [
                'bail',
                'required',
                File::types(['json'])->max(2 * 1024000),
                function (string $attribute, mixed $value, Closure $fail) {
                    $contents = file_get_contents($value->getRealPath());

                    $details = json_decode($contents, true);

                    if (json_last_error() !== JSON_ERROR_NONE || !isset($details['data'])) {
                        $fail("The file has invalid JSON content.");
                    }


                }
            ]
        ]);

        $result = $this->service->verify($request->file('certificate'));

        return JsonResource::make([
            'issuer'    => $result['issuer'],
            'result'    => $result['result']
        ]);
    }

    public function results()
    {
        $results = $this->service->getResults();

        return JsonResource::collection($results);
    }
}
