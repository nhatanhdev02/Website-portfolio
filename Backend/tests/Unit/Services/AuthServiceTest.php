<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\Admin\AuthService;
use App\Models\Admin;
use App\Exceptions\Admin\AdminAuthException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;
use Mockery;

class AuthServiceTest extends TestCase
{
    private AuthService $authService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authService = new AuthService();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_authenticate_admin_with_valid_credentials(): void
    {
        // Arrange
        $admin = Mockery::mock(Admin::class);
        $admin->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $admin->shouldReceive('getAttribute')->with('username')->andReturn('admin');
        $admin->shouldReceive('getAttribute')->with('email')->andReturn('admin@test.com');
        $admin->shouldReceive('getAttribute')->with('password')->andReturn(Hash::make('password123'));
        $admin->shouldReceive('getAttribute')->with('last_login_at')->andReturn(now());
        $admin->shouldReceive('updateLastLogin')->once();
        $admin->shouldReceive('createToken')->with('admin-token', ['admin'])->once()->andReturn(
            (object)['plainTextToken' => 'test-token']
        );

        Admin::shouldReceive('where')->with('username', 'admin')->once()->andReturn(
            Mockery::mock()->shouldReceive('first')->once()->andReturn($admin)->getMock()
        );

        Hash::shouldReceive('check')->with('password123', Mockery::any())->once()->andReturn(true);
        Log::shouldReceive('info')->once();

        // Act
        $result = $this->authService->authenticate('admin', 'password123');

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('user', $result);
        $this->assertEquals('test-token', $result['token']);
        $this->assertEquals(1, $result['user']['id']);
        $this->assertEquals('admin', $result['user']['username']);
    }

    /** @test */
    public function it_throws_exception_for_invalid_credentials(): void
    {
        // Arrange
        Admin::shouldReceive('where')->with('username', 'invalid')->once()->andReturn(
            Mockery::mock()->shouldReceive('first')->once()->andReturn(null)->getMock()
        );

        Log::shouldReceive('warning')->once();

        // Act & Assert
        $this->expectException(AdminAuthException::class);
        $this->authService->authenticate('invalid', 'password');
    }

    /** @test */
    public function it_throws_exception_for_wrong_password(): void
    {
        // Arrange
        $admin = Mockery::mock(Admin::class);
        $admin->shouldReceive('getAttribute')->with('password')->andReturn(Hash::make('correct'));

        Admin::shouldReceive('where')->with('username', 'admin')->once()->andReturn(
            Mockery::mock()->shouldReceive('first')->once()->andReturn($admin)->getMock()
        );

        Hash::shouldReceive('check')->with('wrong', Mockery::any())->once()->andReturn(false);
        Log::shouldReceive('warning')->once();

        // Act & Assert
        $this->expectException(AdminAuthException::class);
        $this->authService->authenticate('admin', 'wrong');
    }

    /** @test */
    public function it_can_logout_admin(): void
    {
        // Arrange
        $token = Mockery::mock();
        $token->shouldReceive('delete')->once()->andReturn(true);

        $admin = Mockery::mock(Admin::class);
        $admin->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $admin->shouldReceive('currentAccessToken')->once()->andReturn($token);

        Log::shouldReceive('info')->once();

        // Act
        $result = $this->authService->logout($admin);

        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function it_handles_logout_failure(): void
    {
        // Arrange
        $admin = Mockery::mock(Admin::class);
        $admin->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $admin->shouldReceive('currentAccessToken')->once()->andThrow(new \Exception('Token error'));

        Log::shouldReceive('error')->once();

        // Act
        $result = $this->authService->logout($admin);

        // Assert
        $this->assertFalse($result);
    }

    /** @test */
    public function it_can_refresh_token(): void
    {
        // Arrange
        $oldToken = Mockery::mock();
        $oldToken->shouldReceive('delete')->once();

        $admin = Mockery::mock(Admin::class);
        $admin->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $admin->shouldReceive('getAttribute')->with('username')->andReturn('admin');
        $admin->shouldReceive('getAttribute')->with('email')->andReturn('admin@test.com');
        $admin->shouldReceive('getAttribute')->with('last_login_at')->andReturn(now());
        $admin->shouldReceive('currentAccessToken')->once()->andReturn($oldToken);
        $admin->shouldReceive('createToken')->with('admin-token', ['admin'])->once()->andReturn(
            (object)['plainTextToken' => 'new-token']
        );

        Log::shouldReceive('info')->once();

        // Act
        $result = $this->authService->refreshToken($admin);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('token', $result);
        $this->assertEquals('new-token', $result['token']);
    }

    /** @test */
    public function it_can_get_current_user_from_token(): void
    {
        // Arrange
        $admin = Mockery::mock(Admin::class);

        $accessToken = Mockery::mock(PersonalAccessToken::class);
        $accessToken->shouldReceive('getAttribute')->with('tokenable')->andReturn($admin);

        PersonalAccessToken::shouldReceive('findToken')->with('test-token')->once()->andReturn($accessToken);

        // Act
        $result = $this->authService->getCurrentUser('test-token');

        // Assert
        $this->assertInstanceOf(Admin::class, $result);
    }

    /** @test */
    public function it_throws_exception_for_invalid_token(): void
    {
        // Arrange
        PersonalAccessToken::shouldReceive('findToken')->with('invalid-token')->once()->andReturn(null);

        // Act & Assert
        $this->expectException(AdminAuthException::class);
        $this->authService->getCurrentUser('invalid-token');
    }

    /** @test */
    public function it_validates_password_strength(): void
    {
        // Act & Assert - Valid password
        $this->assertTrue($this->authService->validatePassword('StrongPass123!'));
    }

    /** @test */
    public function it_throws_exception_for_weak_password(): void
    {
        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->authService->validatePassword('weak');
    }

    /** @test */
    public function it_can_check_session_validity(): void
    {
        // Arrange
        $admin = Mockery::mock(Admin::class);
        $admin->shouldReceive('hasRecentLogin')->with(24)->once()->andReturn(true);

        // Act
        $result = $this->authService->isSessionValid($admin);

        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function it_can_revoke_all_tokens(): void
    {
        // Arrange
        $tokens = Mockery::mock();
        $tokens->shouldReceive('delete')->once()->andReturn(true);

        $admin = Mockery::mock(Admin::class);
        $admin->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $admin->shouldReceive('getAttribute')->with('username')->andReturn('admin');
        $admin->shouldReceive('tokens')->once()->andReturn($tokens);

        Log::shouldReceive('info')->once();

        // Act
        $result = $this->authService->revokeAllTokens($admin);

        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function it_handles_token_revocation_failure(): void
    {
        // Arrange
        $admin = Mockery::mock(Admin::class);
        $admin->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $admin->shouldReceive('tokens')->once()->andThrow(new \Exception('Database error'));

        Log::shouldReceive('error')->once();

        // Act
        $result = $this->authService->revokeAllTokens($admin);

        // Assert
        $this->assertFalse($result);
    }
}
