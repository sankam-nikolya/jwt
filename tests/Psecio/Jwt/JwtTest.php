<?php

namespace Psecio\Jwt;

require_once "ClaimStub.php";

class JwtTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test the getter/setter for the Header instance
     */
    public function testGetSetHeader()
    {
        $header = new Header('test');
        $jwt = new Jwt($header);
        $this->assertSame($jwt->getHeader(), $header);

        $header = new Header('test', 'HS384');
        $jwt->setHeader($header);
        $this->assertSame($jwt->getHeader(), $header);
    }

    /**
     * Testing the getter/setter for the Claims data
     */
    public function testGetSetClaimsCollection()
    {
        $claims = new ClaimsCollection();
        $claim = new \Psecio\Jwt\ClaimStub('test1234');
        $claims->add($claim);

        $header = new Header('test');
        $jwt = new Jwt($header, $claims);

        $this->assertEquals(count($claims), 1);
        $this->assertSame($jwt->getClaims(), $claims);
    }

    /**
     * Testing the addition of a claim via the JWT class
     */
    public function testAddClaim()
    {
        $header = new Header('test');
        $jwt = new Jwt($header);

        $claim = new \Psecio\Jwt\ClaimStub('test1234');
        $jwt->addClaim($claim);

        $this->assertEquals(count($jwt->getClaims()), 1);
    }

    /**
     * Testing the custom base64 encoding
     */
    public function testBase64Encode()
    {
        $string = 'this is my + string !@#$';
        $header = new Header('test');
        $jwt = new Jwt($header);

        $result = $jwt->base64Encode($string);
        $this->assertEquals($result, 'dGhpcyBpcyBteSArIHN0cmluZyAhQCMk');
    }

    /**
     * Testing the custom base64 decoding
     */
    public function testBase64Decode()
    {
        $string = 'dGhpcyBpcyBteSArIHN0cmluZyAhQCMk';
        $header = new Header('test');
        $jwt = new Jwt($header);

        $result = $jwt->base64Decode($string);
        $this->assertEquals($result, 'this is my + string !@#$');
    }

    /**
     * Testing the results of the custom hash_equals function
     */
    public function testHashEquals()
    {
        $hash1 = md5(mt_rand());
        $header = new Header('test');
        $jwt = new Jwt($header);

        $this->assertTrue($jwt->hash_equals($hash1, $hash1));
        $this->assertFalse($jwt->hash_equals($hash1, 'badhash'));
    }

    /**
     * Test the encoding of some data into the JWT format
     */
    public function testEncodeJwtData()
    {
        $header = new Header('test');
        $jwt = new Jwt($header);

        $jwt
            ->issuer('http://example.org')
            ->audience('http://example.com')
            ->issuedAt(1356999524)
            ->notBefore(1357000000)
            ->expireTime(time()+3600)
            ->jwtId('id123456')
            ->type('https://example.com/register')
            ->custom('test', 'claim1');

        $result = $jwt->encode();
        $parts = explode('.', $result);

        $this->assertEquals($parts[0], 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9');
        $this->assertTrue(strstr($parts[1], 'eyJpc3MiOiJodHRwOi8vZXhhbXBsZS5vcmciLCJhdWQiOiJodHRwOi8v') !== false);
    }

    /**
     * Test the decoding of a valid JWT token
     */
    public function testDecodeJwtDataValid()
    {
        $header = new Header('test');
        $jwt = new Jwt($header);

        $jwt
            ->issuer('http://example.org')
            ->audience('http://example.com')
            ->issuedAt(1356999524)
            ->notBefore(1357000000)
            ->expireTime(time()+3600)
            ->jwtId('id123456')
            ->type('https://example.com/register')
            ->custom('test', 'claim1');

        $result = $jwt->encode();
        $decoded = $jwt->decode($result);

        $this->assertEquals($decoded->iss, 'http://example.org');
        $this->assertEquals($decoded->typ, 'https://example.com/register');
    }

    /**
     * Test the decoding of an invalid token signature
     * @expectedException \Psecio\Jwt\Exception\BadSignatureException
     */
    public function testDecodeJwtDataInvalidSignature()
    {
        $header = new Header('test');
        $jwt = new Jwt($header);

        $jwt
            ->issuer('http://example.org')
            ->audience('http://example.com');

        $result = $jwt->encode();
        $result = substr($result, 0, strlen($result) - 10);

        $decoded = $jwt->decode($result);
    }

        /**
     * Test the decoding of an invalid token
     * @expectedException \Psecio\Jwt\Exception\DecodeException
     */
    public function testDecodeJwtDataInvalidSections()
    {
        $header = new Header('test');
        $jwt = new Jwt($header);

        $jwt
            ->issuer('http://example.org')
            ->audience('http://example.com');

        $result = $jwt->encode();
        $result = substr($result, 0, strlen($result) - 100);

        $decoded = $jwt->decode($result);
    }

    /**
     * Test the valid verification of a signature with verify() method
     */
    public function testVerifyJwtSignatureValid()
    {
        $key = 'test';
        $header = new Header($key);
        $jwt = new Jwt($header);

        $jwt
            ->issuer('http://example.org')
            ->audience('http://example.com');

        $header = (object)$jwt->getHeader()->toArray();
        $claims = (object)$jwt->getClaims()->toArray();
        $parts = explode('.', $jwt->encode());
        $signature = $jwt->base64Decode($parts[2]);

        $this->assertTrue($jwt->verify($key, $header, $claims, $signature));
    }

    /**
     * Try the verify call on a JWT with no algorithm in the header
     * @expectedException \Psecio\Jwt\Exception\DecodeException
     */
    public function testVerifyJwtSignatureNoAlg()
    {
        $key = 'test';
        $header = new Header($key);
        $jwt = new Jwt($header);

        $jwt
            ->issuer('http://example.org')
            ->audience('http://example.com');

        $claims = (object)$jwt->getClaims()->toArray();
        $parts = explode('.', $jwt->encode());
        $signature = $jwt->base64Decode($parts[2]);

        $header->setAlgorithm(null);
        $header = (object)$jwt->getHeader()->toArray();

        $this->assertTrue($jwt->verify($key, $header, $claims, $signature));
    }

    /**
     * Try the verify call on a JWT with no audience defined in the header
     * @expectedException \Psecio\Jwt\Exception\DecodeException
     */
    public function testVerifyJwtSignatureNoAudience()
    {
        $key = 'test';
        $header = new Header($key);
        $jwt = new Jwt($header);
        $jwt->issuer('http://example.org');

        $claims = (object)$jwt->getClaims()->toArray();
        $parts = explode('.', $jwt->encode());
        $signature = $jwt->base64Decode($parts[2]);
        $header = (object)$jwt->getHeader()->toArray();

        $this->assertTrue($jwt->verify($key, $header, $claims, $signature));
    }

    /**
     * Try the verify call on a JWT with no expiration defined in the header
     * @expectedException \Psecio\Jwt\Exception\DecodeException
     */
    public function testVerifyJwtSignaturePastExpire()
    {
        $key = 'test';
        $header = new Header($key);
        $jwt = new Jwt($header);
        $jwt->issuer('http://example.org')
            ->audience('http://example.com')
            ->expireTime(time()-3600);

        $claims = (object)$jwt->getClaims()->toArray();
        $parts = explode('.', $jwt->encode());
        $signature = $jwt->base64Decode($parts[2]);
        $header = (object)$jwt->getHeader()->toArray();

        $this->assertTrue($jwt->verify($key, $header, $claims, $signature));
    }

    /**
     * Try the verify call on a JWT with no expiration defined in the header
     * @expectedException \Psecio\Jwt\Exception\DecodeException
     */
    public function testVerifyJwtSignatureNotProcessBefore()
    {
        $key = 'test';
        $header = new Header($key);
        $jwt = new Jwt($header);
        $jwt->issuer('http://example.org')
            ->audience('http://example.com')
            ->expireTime(time()+3600)
            ->notBefore(time()+3600);

        $claims = (object)$jwt->getClaims()->toArray();
        $parts = explode('.', $jwt->encode());
        $signature = $jwt->base64Decode($parts[2]);
        $header = (object)$jwt->getHeader()->toArray();

        $this->assertTrue($jwt->verify($key, $header, $claims, $signature));
    }

    /**
     * Test the signing with private key data (verify decode)
     */
    public function testSignWithPrivateKeyValid()
    {
        $keyPath = 'file://'.__DIR__.'/../../private.pem';
        $key = openssl_pkey_get_private($keyPath, 'test1234');
        $header = new \Psecio\Jwt\Header($key);
        $header->setHashMethod('RS256');

        $jwt = new \Psecio\Jwt\Jwt($header);
        $jwt->audience('http://example.com');

        $result = $jwt->encode();
        $result = $jwt->decode($result);

        $this->assertEquals($result->aud, 'http://example.com');
    }

    /**
     * Test the signing with private key data (verify decode)
     * @expectedException \InvalidArgumentException
     */
    public function testSignWithPrivateKeyBadHashType()
    {
        $keyPath = 'file://'.__DIR__.'/../../private.pem';
        $key = openssl_pkey_get_private($keyPath, 'test1234');
        $header = new \Psecio\Jwt\Header($key);
        $header->setHashMethod('RS1234');

        $jwt = new \Psecio\Jwt\Jwt($header);
        $jwt->audience('http://example.com');

        $result = $jwt->encode();
        $result = $jwt->decode($result);

        $this->assertEquals($result->aud, 'http://example.com');
    }

    /**
     * Test the signing with private key data (verify decode)
     * @expectedException \Psecio\Jwt\Exception\InvalidKeyException
     */
    public function testSignWithPrivateKeyInvalidKey()
    {
        $keyPath = 'file://'.__DIR__.'/../../private.pem';
        $header = new \Psecio\Jwt\Header('test');
        $header->setHashMethod('RS256');

        $jwt = new \Psecio\Jwt\Jwt($header);
        $jwt->audience('http://example.com');

        $result = $jwt->encode();
        $result = $jwt->decode($result);

        $this->assertEquals($result->aud, 'http://example.com');
    }

    /**
     * Test the signing with private key data (verify decode)
     * @expectedException \Psecio\Jwt\Exception\InvalidKeyException
     */
    public function testSignWithPrivateKeySignFailure()
    {
        $keyPath = 'file://'.__DIR__.'/../../private.pem';
        $header = new \Psecio\Jwt\Header('test');
        $header->setHashMethod('RS256');

        $jwt = new \Psecio\Jwt\Jwt($header);
        $jwt->audience('http://example.com');

        $result = $jwt->encode();
        $result = $jwt->decode($result);

        $this->assertEquals($result->audience, 'http://example.com');
    }
}