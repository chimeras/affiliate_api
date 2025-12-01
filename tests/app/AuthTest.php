<?php
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\CIUnitTestCase;

class AuthTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testRegisterValidation()
    {
        $result = $this->post('/auth/register', ['email' => 'bad', 'password' => '123', 'role' => 'student']);
        $result->assertStatus(400);
    }
}
