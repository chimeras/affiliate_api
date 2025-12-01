<?php
namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\API\ResponseTrait;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthController extends BaseController
{
    use ResponseTrait;

    public function register()
    {
        $rules = [
            'email' => 'required|valid_email|is_unique[users.email]',
            'password' => 'required|min_length[8]',
            'role' => 'required|in_list[student,instructor]'
        ];

        if (! $this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        $userModel = new UserModel();
        $data = [
            'email' => $this->request->getPost('email'),
            'password_hash' => password_hash($this->request->getPost('password'), PASSWORD_BCRYPT),
            'role' => $this->request->getPost('role'),
            'status' => 'pending',
            'verification_token' => bin2hex(random_bytes(32)),
        ];
        $userId = $userModel->insert($data);

        // TODO: queue verification email job
        return $this->respondCreated(['id' => $userId, 'message' => 'User registered. Please verify email.']);
    }

    public function login()
    {
        $userModel = new UserModel();
        $user = $userModel->where('email', $this->request->getPost('email'))->first();
        if (! $user || ! password_verify($this->request->getPost('password'), $user['password_hash'])) {
            return $this->failUnauthorized('Invalid credentials');
        }
        if ($user['status'] !== 'active') {
            return $this->fail('Account not verified');
        }

        $payload = [
            'sub' => $user['id'],
            'role' => $user['role'],
            'iat' => time(),
            'exp' => time() + 3600
        ];
        $token = JWT::encode($payload, getenv('JWT_SECRET') ?: 'secret', 'HS256');
        return $this->respond(['token' => $token]);
    }

    public function verify($token)
    {
        $userModel = new UserModel();
        $user = $userModel->where('verification_token', $token)->first();
        if (! $user) {
            return $this->failNotFound('Invalid token');
        }
        $userModel->update($user['id'], ['status' => 'active', 'verification_token' => null]);
        return $this->respond(['message' => 'Verified']);
    }

    public function forgot()
    {
        $email = $this->request->getPost('email');
        $userModel = new UserModel();
        $user = $userModel->where('email', $email)->first();
        if (! $user) {
            return $this->respond(['message' => 'If the email exists a reset link was sent']);
        }
        $token = bin2hex(random_bytes(32));
        $userModel->update($user['id'], ['reset_token' => $token]);
        // TODO: queue password reset email job
        return $this->respond(['message' => 'Reset email queued']);
    }

    public function reset()
    {
        $rules = [
            'token' => 'required',
            'password' => 'required|min_length[8]'
        ];
        if (! $this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }
        $userModel = new UserModel();
        $user = $userModel->where('reset_token', $this->request->getPost('token'))->first();
        if (! $user) {
            return $this->failNotFound('Invalid token');
        }
        $userModel->update($user['id'], [
            'password_hash' => password_hash($this->request->getPost('password'), PASSWORD_BCRYPT),
            'reset_token' => null
        ]);
        return $this->respond(['message' => 'Password updated']);
    }

    public function logout()
    {
        return $this->respond(['message' => 'Logged out']);
    }
}
