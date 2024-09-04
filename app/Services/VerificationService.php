<?php

namespace App\Services;

use App\Models\VerificationResult;
use App\VerificationResults;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

class VerificationService
{
    public function verify(UploadedFile $file): array
    {
        $userId = auth() ? auth()->user()?->id : null;

        $contents = file_get_contents($file->getRealPath());

        $details = json_decode($contents, true);

        // check for valid recipient
        if (!isset($details['data']['recipient']['name']) || !isset($details['data']['recipient']['email'])) {
            $this->saveResult(VerificationResults::INVALID_RECIPIENT, $userId);
            return [
                'issuer' => $details['data']['issuer']['name'],
                'result'    => VerificationResults::INVALID_RECIPIENT
            ];
        }

        // check for valid issuer
        if (!isset($details['data']['issuer']['name']) || !isset($details['data']['issuer']['identityProof'])) {
            $this->saveResult(VerificationResults::INVALID_ISSUER, $userId);
            return [
                'issuer' => "Unknown",
                'result'    => VerificationResults::INVALID_ISSUER
            ];
        }

        $response = Http::get('https://dns.google/resolve?name=' . $details['data']['issuer']['identityProof']['location'] . '&type=TXT');

        /** @var array  */
        $answers = $response->json('Answer');

        /** @var bool */
        $found = false;

        if (is_null($answers)) {
            $this->saveResult(VerificationResults::INVALID_ISSUER, $userId);
            return [
                'issuer' => $details['data']['issuer']['name'],
                'result'    => VerificationResults::INVALID_ISSUER
            ];
        }

        foreach ($answers as $answer) {
            if (preg_match('/(did:eth[^;]*);/', $answer['data'], $match)) {
                if ($found =  $match[1] == $details['data']['issuer']['identityProof']['key']) {
                    break;
                }
            }
        }

        if (!$found) {
            $this->saveResult(VerificationResults::INVALID_ISSUER, $userId);
            return [
                'issuer' => $details['data']['issuer']['name'],
                'result'    => VerificationResults::INVALID_ISSUER
            ];
        }

        // check for valid signature
        $hashes = [];
        foreach ($this->jsonToDotNotation($details['data']) as $key => $detail) {
            array_push($hashes, hash('sha256', json_encode([$key => $detail])));
        }

        sort($hashes);

        $targetHash = hash('sha256', json_encode($hashes));

        if ($details['signature']['targetHash'] !== $targetHash) {
            $this->saveResult(VerificationResults::INVALID_SIGNATURE, $userId);
            return [
                'issuer' => $details['data']['issuer']['name'],
                'result'    => VerificationResults::INVALID_SIGNATURE
            ];
        }

        $this->saveResult(VerificationResults::VERIFIED, $userId);
        return [
            'issuer'    => $details['data']['issuer']['name'],
            'result'    => VerificationResults::VERIFIED
        ];
    }

    private function jsonToDotNotation(array $array, string $prefix = ''): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $dotKey = $prefix === '' ? $key : $prefix . '.' . $key;

            if (is_array($value)) {
                $result = array_merge($result, $this->jsonToDotNotation($value, $dotKey));
            } else {
                $result[$dotKey] = $value;
            }
        }

        return $result;
    }

    private function saveResult(string $result, int $userId = null, string $fileType = 'json'): VerificationResult
    {
        return VerificationResult::create([
            'user_id'   => $userId,
            'result'    => $result,
            'file_type' => $fileType
        ]);
    }

    public function getResults(): \Illuminate\Pagination\LengthAwarePaginator
    {
        $userId = auth() ? auth()->user()?->id : null;

        return VerificationResult::when(!is_null($userId), fn($query) => $query->where('user_id', $userId))->paginate();
    }
}
