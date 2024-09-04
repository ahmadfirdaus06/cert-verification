<?php

namespace Tests\Feature\CredentialVerificationController;

use App\Models\VerificationResult;
use App\VerificationResults;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class VerifyTest extends TestCase
{
    use RefreshDatabase;

    public function testVerifyWithoutUploadFile(): void
    {
        $response = $this->postJson(route('certificates.verify'));

        $response->assertUnprocessable();
    }

    public function testVerifyWithInvalidFileType(): void
    {
        $response = $this->postJson(route('certificates.verify'), [
            'certificate' => UploadedFile::fake()->create('cert.pdf')
        ]);

        $response->assertUnprocessable();
    }

    public function testVerifyWithExceedFileSizeLimit(): void
    {
        $response = $this->postJson(route('certificates.verify'), [
            'certificate' => UploadedFile::fake()->create('cert.json', 3 * 1024000)
        ]);

        $response->assertUnprocessable();
    }

    public function testVerifyWithInvalidJsonContent(): void
    {
        $response = $this->postJson(route('certificates.verify'), [
            'certificate' => UploadedFile::fake()->createWithContent('cert.json', fake()->text())
        ]);

        $response->assertUnprocessable();
    }

    public static function sampleCerts(): array
    {
        return [
            'missing recipient property details' => [
                [
                    "data" => [
                        "id" => "63c79bd9303530645d1cca00",
                        "name" => "Certificate of Completion",
                        "issuer" => [
                            "name" => "Accredify",
                            "identityProof" => [
                                "type" => "DNS-DID",
                                "key" => "did:ethr:0x05b642ff12a4ae545357d82ba4f786f3aed84214#controller",
                                "location" => "ropstore.accredify.io",
                            ],
                        ],
                        "issued" => "2022-12-23T00:00:00+08:00",
                    ],
                    "signature" => [
                        "type" => "SHA3MerkleProof",
                        "targetHash" => "288f94aadadf486cfdad84b9f4305f7d51eac62db18376d48180cc1dd2047a0e",
                    ],
                ],
                VerificationResults::INVALID_RECIPIENT
            ],
            'missing issuer property details' => [
                [
                    "data" => [
                        "id" => "63c79bd9303530645d1cca00",
                        "name" => "Certificate of Completion",
                        "recipient" => [
                            "name" => "Marty McFly",
                            "email" => "marty.mcfly@gmail.com",
                        ],
                        "issued" => "2022-12-23T00:00:00+08:00",
                    ],
                    "signature" => [
                        "type" => "SHA3MerkleProof",
                        "targetHash" => "288f94aadadf486cfdad84b9f4305f7d51eac62db18376d48180cc1dd2047a0e",
                    ],
                ],
                VerificationResults::INVALID_ISSUER
            ],
            'invalid issuer identify proof location details' => [
                [
                    "data" => [
                        "id" => "63c79bd9303530645d1cca00",
                        "name" => "Certificate of Completion",
                        "recipient" => [
                            "name" => "Marty McFly",
                            "email" => "marty.mcfly@gmail.com",
                        ],
                        "issuer" => [
                            "name" => "Accredify",
                            "identityProof" => [
                                "type" => "DNS-DID",
                                "key" => "did:ethr:0x05b642ff12a4ae545357d82ba4f786f3aed84214#controller",
                                "location" => fake()->domainName(),
                            ],
                        ],
                        "issued" => "2022-12-23T00:00:00+08:00",
                    ],
                    "signature" => [
                        "type" => "SHA3MerkleProof",
                        "targetHash" => "288f94aadadf486cfdad84b9f4305f7d51eac62db18376d48180cc1dd2047a0e",
                    ],
                ],
                VerificationResults::INVALID_ISSUER
            ],
            'invalid issuer identity proof key details' => [
                [
                    "data" => [
                        "id" => "63c79bd9303530645d1cca00",
                        "name" => "Certificate of Completion",
                        "recipient" => [
                            "name" => "Marty McFly",
                            "email" => "marty.mcfly@gmail.com",
                        ],
                        "issuer" => [
                            "name" => "Accredify",
                            "identityProof" => [
                                "type" => "DNS-DID",
                                "key" => fake()->randomAscii(),
                                "location" => 'ropstore.accredify.io',
                            ],
                        ],
                        "issued" => "2022-12-23T00:00:00+08:00",
                    ],
                    "signature" => [
                        "type" => "SHA3MerkleProof",
                        "targetHash" => "288f94aadadf486cfdad84b9f4305f7d51eac62db18376d48180cc1dd2047a0e",
                    ],
                ],
                VerificationResults::INVALID_ISSUER
            ],
            'invalid hash signature' => [
                [
                    "data" => [
                        "id" => fake()->uuid(),
                        "name" => "Certificate of Completion",
                        "recipient" => [
                            "name" => "Marty McFly",
                            "email" => "marty.mcfly@gmail.com",
                        ],
                        "issuer" => [
                            "name" => "Accredify",
                            "identityProof" => [
                                "type" => "DNS-DID",
                                "key" => "did:ethr:0x05b642ff12a4ae545357d82ba4f786f3aed84214#controller",
                                "location" => 'ropstore.accredify.io',
                            ],
                        ],
                        "issued" => "2022-12-23T00:00:00+08:00",
                    ],
                    "signature" => [
                        "type" => "SHA3MerkleProof",
                        "targetHash" => "288f94aadadf486cfdad84b9f4305f7d51eac62db18376d48180cc1dd2047a0e",
                    ],
                ],
                VerificationResults::INVALID_SIGNATURE
            ]
        ];
    }

    #[DataProvider('sampleCerts')]
    public function testVerifyWithInvalidContentVerification(array $fileContent, string $expectedResult)
    {
        $response = $this->postJson(route('certificates.verify'), [
            'certificate' => UploadedFile::fake()->createWithContent('cert.json', json_encode($fileContent))
        ]);

        $response->assertSuccessful();
        $this->assertEquals($expectedResult, $response->decodeResponseJson()['data']['result']);
        $result = VerificationResult::first();
        $this->assertEquals($result->result, $expectedResult);
    }

    public function testVerifySuccessfully(): void
    {
        $response = $this->postJson(route('certificates.verify'), [
            'certificate' => UploadedFile::fake()->createWithContent('cert.json', json_encode([
                "data" => [
                    "id" => "63c79bd9303530645d1cca00",
                    "name" => "Certificate of Completion",
                    "recipient" => [
                        "name" => "Marty McFly",
                        "email" => "marty.mcfly@gmail.com",
                    ],
                    "issuer" => [
                        "name" => "Accredify",
                        "identityProof" => [
                            "type" => "DNS-DID",
                            "key" => "did:ethr:0x05b642ff12a4ae545357d82ba4f786f3aed84214#controller",
                            "location" => "ropstore.accredify.io",
                        ],
                    ],
                    "issued" => "2022-12-23T00:00:00+08:00",
                ],
                "signature" => [
                    "type" => "SHA3MerkleProof",
                    "targetHash" => "288f94aadadf486cfdad84b9f4305f7d51eac62db18376d48180cc1dd2047a0e",
                ],
            ]))
        ]);

        $response->assertSuccessful();
        $this->assertEquals(VerificationResults::VERIFIED, $response->decodeResponseJson()['data']['result']);
        $result = VerificationResult::first();
        $this->assertEquals($result->result, VerificationResults::VERIFIED);
    }

    public function testGetVerificationResults()
    {
        $total = 5;
        VerificationResult::factory()->count($total)->create();

        $response = $this->getJson(route('certificates.results'));

        $this->assertCount($total, $response->decodeResponseJson()['data']);
    }
}
